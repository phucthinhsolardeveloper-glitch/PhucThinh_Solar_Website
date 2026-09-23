const { test, expect } = require('@playwright/test');

test.beforeEach(async ({ page }) => {
  await page.route('https://cdn.gtranslate.net/**', route => route.abort());
});

async function expectThreeDigitQuantityFits(counter) {
  const input = counter.locator('input.quantity-procart');
  await input.evaluate(node => { node.value = '999'; });
  const [parts, metrics] = await Promise.all([
    counter.locator('button, input').evaluateAll(nodes => nodes.map(node => {
      const rect = node.getBoundingClientRect();
      return { left: rect.left, right: rect.right };
    })),
    input.evaluate(node => ({ value: node.value, clientWidth: node.clientWidth, scrollWidth: node.scrollWidth }))
  ]);
  expect(parts).toHaveLength(3);
  expect(parts[0].right).toBeLessThanOrEqual(parts[1].left + 1);
  expect(parts[1].right).toBeLessThanOrEqual(parts[2].left + 1);
  expect(metrics.value).toBe('999');
  expect(metrics.scrollWidth).toBeLessThanOrEqual(metrics.clientWidth);
}

test('desktop checkout keeps payment rows fully clickable and quantity horizontal', async ({ page, isMobile }) => {
  test.skip(isMobile, 'Kiểm tra hình học control checkout desktop.');
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.goto('/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w', { waitUntil: 'domcontentloaded' });

  const addButton = page.locator('.addcart[data-action="addnow"]').first();
  await expect(addButton).toBeVisible();
  await addButton.click();
  await page.waitForTimeout(500);
  const popupCounter = page.locator('#popup-cart .quantity-counter-procart').first();
  await expect(popupCounter).toBeVisible();
  await expectThreeDigitQuantityFits(popupCounter);
  await page.goto('/gio-hang/', { waitUntil: 'domcontentloaded' });

  const paymentRow = page.locator('.payments-cart').first();
  const paymentLabel = paymentRow.locator('.payments-label');
  const paymentInput = paymentRow.locator('.custom-control-input');
  await expect(paymentRow).toBeVisible();

  const [rowBox, labelBox] = await Promise.all([paymentRow.boundingBox(), paymentLabel.boundingBox()]);
  expect(Math.abs(rowBox.width - labelBox.width)).toBeLessThanOrEqual(2);
  await paymentLabel.click({ position: { x: labelBox.width - 16, y: labelBox.height / 2 } });
  await expect(paymentInput).toBeChecked();
  const selectedRowHeight = await paymentRow.evaluate(node => node.getBoundingClientRect().height);
  if (!(await paymentRow.locator('.payments-info').count())) {
    expect(selectedRowHeight).toBeLessThanOrEqual(labelBox.height + 2);
  }

  const counter = page.locator('.form-cart .quantity-counter-procart').first();
  await expect(counter).toBeVisible();
  await expectThreeDigitQuantityFits(counter);
  const pieces = counter.locator('button, input');
  await expect(pieces).toHaveCount(3);
  const boxes = await pieces.evaluateAll(nodes => nodes.map(node => {
    const rect = node.getBoundingClientRect();
    return { x: rect.x, y: rect.y, width: rect.width, height: rect.height };
  }));
  expect(Math.max(...boxes.map(box => box.y)) - Math.min(...boxes.map(box => box.y))).toBeLessThanOrEqual(1);
  for (const box of boxes) expect(box.height).toBeLessThanOrEqual(42);
  expect(boxes[0].x).toBeLessThan(boxes[1].x);
  expect(boxes[1].x).toBeLessThan(boxes[2].x);
  const product = page.locator('.form-cart .procart:not(.procart-label)').first();
  const [nameBox, priceBox, counterBox, removeBox] = await Promise.all([
    product.locator('.info-procart').boundingBox(),
    product.locator('.procart-price-desktop .price-new-cart').boundingBox(),
    counter.boundingBox(),
    product.locator('.del-procart').boundingBox()
  ]);
  const verticalCenter = box => box.y + box.height / 2;
  expect(Math.abs(verticalCenter(nameBox) - verticalCenter(priceBox))).toBeLessThanOrEqual(1);
  expect(Math.abs(verticalCenter(nameBox) - verticalCenter(counterBox))).toBeLessThanOrEqual(1);
  expect(removeBox.y).toBeGreaterThan(counterBox.y + counterBox.height);
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
});

test('mobile checkout controls stay horizontal and inside the viewport', async ({ page, isMobile }) => {
  test.skip(!isMobile, 'Kiểm tra hồi quy checkout mobile.');
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w', { waitUntil: 'domcontentloaded' });
  await page.locator('.addcart[data-action="addnow"]').first().click();
  await page.waitForTimeout(500);
  await page.goto('/gio-hang/', { waitUntil: 'domcontentloaded' });

  const paymentRow = page.locator('.payments-cart').first();
  const paymentLabel = paymentRow.locator('.payments-label');
  const [rowBox, labelBox] = await Promise.all([paymentRow.boundingBox(), paymentLabel.boundingBox()]);
  expect(Math.abs(rowBox.width - labelBox.width)).toBeLessThanOrEqual(2);
  const [mainBox, sectionBox, mainPaddingLeft] = await Promise.all([
    page.locator('.wrap-main').boundingBox(),
    page.locator('.section-cart').boundingBox(),
    page.locator('.wrap-main').evaluate(node => parseFloat(getComputedStyle(node).paddingLeft))
  ]);
  expect(Math.abs((mainBox.x + mainPaddingLeft) - sectionBox.x)).toBeLessThanOrEqual(1);

  const boxes = await page.locator('.form-cart .quantity-counter-procart').first()
    .locator('button, input').evaluateAll(nodes => nodes.map(node => {
      const rect = node.getBoundingClientRect();
      return { x: rect.x, y: rect.y, height: rect.height };
    }));
  expect(Math.max(...boxes.map(box => box.y)) - Math.min(...boxes.map(box => box.y))).toBeLessThanOrEqual(1);
  expect(boxes[0].x).toBeLessThan(boxes[1].x);
  expect(boxes[1].x).toBeLessThan(boxes[2].x);
  await expectThreeDigitQuantityFits(page.locator('.form-cart .quantity-counter-procart').first());
  expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
});

test('checkout address uses only province and ward levels', async ({ page, isMobile }) => {
  test.skip(isMobile, 'Kiểm tra luồng địa chỉ checkout trên desktop.');
  await page.setViewportSize({ width: 1440, height: 1000 });
  await page.goto('/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w', { waitUntil: 'domcontentloaded' });
  await page.locator('.addcart[data-action="addnow"]').first().click();
  await page.waitForTimeout(500);
  await page.goto('/gio-hang/', { waitUntil: 'domcontentloaded' });

  await expect(page.locator('.select-district-cart')).toHaveCount(0);
  const city = page.locator('.select-city-cart');
  const ward = page.locator('.select-ward-cart');
  expect(await city.locator('option').count()).toBeGreaterThanOrEqual(34);
  await city.selectOption({ index: 1 });
  await expect.poll(async () => ward.locator('option').count()).toBeGreaterThan(1);
  expect(await ward.locator('option').count()).toBeGreaterThan(1);
});
