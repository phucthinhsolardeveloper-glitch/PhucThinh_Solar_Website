const { test, expect } = require('@playwright/test');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');

const sidebarScript = path.resolve(__dirname, '../../../domains/phucthinhsolar.com/public_html/administrator/assets/js/sidebar-state.js');
const destination = '/administrator/index.php?com=seopage&act=update&type=lien-he';

function sidebarMarkup(active = false, topSpace = 620) {
  return `
    <aside class="main-sidebar">
      <div class="sidebar" style="height:220px;overflow-y:auto">
        <ul class="nav-sidebar">
          <li style="height:${topSpace}px"></li>
          <li class="nav-item has-treeview">
            <a class="nav-link" href="#">Quản lý SEO page</a>
            <ul class="nav-treeview">
              <li class="nav-item">
                <a id="contact-link" class="nav-link${active ? ' active' : ''}" href="${destination}" onclick="event.preventDefault()">Liên hệ</a>
              </li>
            </ul>
          </li>
          <li style="height:700px"></li>
        </ul>
      </div>
    </aside>`;
}

test('admin sidebar preserves the clicked child position after navigation', async ({ page }) => {
  await page.goto('/administrator/index.php?com=user&act=login');
  await page.setContent(sidebarMarkup(false, 620));
  await page.addScriptTag({ path: sidebarScript });

  const sidebar = page.locator('.main-sidebar .sidebar');
  await sidebar.evaluate(element => { element.scrollTop = 500; });
  const originalOffset = await page.locator('#contact-link').evaluate((link) => {
    const sidebarElement = link.closest('.sidebar');
    return link.getBoundingClientRect().top - sidebarElement.getBoundingClientRect().top;
  });
  await page.locator('#contact-link').click();

  await page.evaluate((url) => history.pushState({}, '', url), destination);
  await page.setContent(sidebarMarkup(true, 820));
  await page.addScriptTag({ path: sidebarScript });
  await page.waitForTimeout(100);

  const restored = await page.locator('#contact-link').evaluate((link) => {
    const sidebarElement = link.closest('.sidebar');
    return {
      offset: link.getBoundingClientRect().top - sidebarElement.getBoundingClientRect().top,
      scrollTop: sidebarElement.scrollTop,
      branchOpen: link.closest('.has-treeview').classList.contains('menu-open')
    };
  });

  expect(restored.scrollTop).toBeGreaterThan(500);
  expect(Math.abs(restored.offset - originalOffset)).toBeLessThanOrEqual(2);
  expect(restored.branchOpen).toBe(true);
});

function runFixturePhp(code, args = []) {
  return execFileSync('docker', ['exec', 'pts-php81-local', 'php', '-r', code, ...args], { encoding: 'utf8' }).trim();
}

function createAdminFixture() {
  const credentials = {
    username: `sidebar-${crypto.randomBytes(6).toString('hex')}`,
    password: crypto.randomBytes(18).toString('hex')
  };
  const payload = Buffer.from(JSON.stringify(credentials)).toString('base64');
  const code = `
    $_SERVER['SERVER_NAME']='localhost';
    define('LIBRARIES','/var/www/html/libraries/');
    require LIBRARIES.'config.php';
    require LIBRARIES.'autoload.php';
    new AutoLoad();
    $d=new PDODb($config['database']);
    $cache=new Cache($d);
    $func=new Functions($d,$cache);
    $input=json_decode(base64_decode($argv[1]),true);
    $d->insert('user',array(
      'id_permission'=>0,
      'username'=>$input['username'],
      'password'=>$func->encryptPassword($config['website']['secret'],$input['password'],$config['website']['salt']),
      'fullname'=>'Sidebar browser fixture',
      'status'=>'hienthi',
      'role'=>3,
      'secret_key'=>bin2hex(random_bytes(16)),
      'numb'=>0
    ));
    echo (int)$d->getLastInsertId();
  `;
  return { ...credentials, id: Number(runFixturePhp(code, [payload])) };
}

function removeAdminFixture(id) {
  const code = `
    $_SERVER['SERVER_NAME']='localhost';
    define('LIBRARIES','/var/www/html/libraries/');
    require LIBRARIES.'config.php';
    require LIBRARIES.'autoload.php';
    new AutoLoad();
    $d=new PDODb($config['database']);
    $id=(int)$argv[1];
    $d->rawQuery('delete from #_user_log where id_user = ?',array($id));
    $d->rawQuery('delete from #_user where id = ?',array($id));
  `;
  runFixturePhp(code, [String(id)]);
}

test('real admin SEO child navigation keeps the sidebar position', async ({ page }) => {
  const fixture = createAdminFixture();
  try {
    await page.goto('/administrator/index.php?com=user&act=login');
    await page.locator('#username').fill(fixture.username);
    await page.locator('#password').fill(fixture.password);
    await Promise.all([
      page.waitForURL(/\/administrator\/index\.php$/),
      page.locator('.btn-login').click()
    ]);

    await page.goto('/administrator/index.php?com=seopage&act=update&type=san-pham');
    const isMobile = await page.evaluate(() => window.innerWidth < 768);
    if (isMobile) {
      await page.locator('[data-widget="pushmenu"]').click();
      await expect(page.locator('body')).toHaveClass(/sidebar-open/);
    }
    const sidebar = page.locator('.main-sidebar .sidebar');
    const contactLink = sidebar.locator('a.nav-link[href="index.php?com=seopage&act=update&type=lien-he"]');
    await expect(contactLink).toBeVisible();
    await contactLink.evaluate((link) => {
      const scrollArea = link.closest('.sidebar');
      scrollArea.scrollTop += link.getBoundingClientRect().top - scrollArea.getBoundingClientRect().top - 110;
    });
    await page.waitForTimeout(100);
    const before = await contactLink.evaluate((link) => {
      const scrollArea = link.closest('.sidebar');
      return {
        offset: link.getBoundingClientRect().top - scrollArea.getBoundingClientRect().top,
        scrollTop: scrollArea.scrollTop,
        scrollHeight: scrollArea.scrollHeight,
        clientHeight: scrollArea.clientHeight,
        overflowY: getComputedStyle(scrollArea).overflowY
      };
    });
    expect(before.scrollTop, JSON.stringify(before)).toBeGreaterThan(0);

    await Promise.all([
      page.waitForURL(/com=seopage.*type=lien-he/),
      contactLink.click()
    ]);
    await expect(sidebar).toHaveAttribute('data-scroll-state-ready', '1');
    if (isMobile) {
      await page.locator('[data-widget="pushmenu"]').click();
      await expect(page.locator('body')).toHaveClass(/sidebar-open/);
    }
    await page.waitForTimeout(150);

    const after = await sidebar.locator('a.nav-link.active[href="index.php?com=seopage&act=update&type=lien-he"]').evaluate((link) => {
      const scrollArea = link.closest('.sidebar');
      return {
        offset: link.getBoundingClientRect().top - scrollArea.getBoundingClientRect().top,
        scrollTop: scrollArea.scrollTop,
        branchOpen: link.closest('.has-treeview').classList.contains('menu-open')
      };
    });
    expect(after.scrollTop).toBeGreaterThan(0);
    expect(Math.abs(after.offset - before.offset)).toBeLessThanOrEqual(3);
    expect(after.branchOpen).toBe(true);
  } finally {
    removeAdminFixture(fixture.id);
  }
});
