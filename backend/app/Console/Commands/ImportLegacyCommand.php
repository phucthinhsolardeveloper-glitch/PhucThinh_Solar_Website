<?php

namespace App\Console\Commands;

use App\Support\ContentBlocks;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * One-time move of the PHP site's data (connection "legacy") into the new schema.
 * Text the PHP Admin stored HTML-escaped is decoded once; "a,b" status strings become flags.
 * Ids are kept where the old table is kept whole, so links and filters keep working; sub-categories
 * get new ids (they shared numbers with top categories) and the map is written for the API checks.
 */
class ImportLegacyCommand extends Command
{
    protected $signature = 'pts:import-legacy {--fresh : Empty the new tables first}';

    protected $description = 'Copy the PHP site data into the new Laravel schema.';

    private Connection $old;

    private Connection $new;

    /** legacy level+id => new category id */
    private array $categoryMap = [];

    private const NEW_TABLES = ['review_media', 'reviews', 'payment_transactions', 'order_items', 'orders', 'leads', 'wards', 'provinces', 'post_revisions', 'post_products', 'post_media', 'posts', 'post_categories',
        'content_sections', 'product_documents', 'product_images', 'product_options', 'products', 'brands', 'categories', 'page_items', 'banners', 'store_locations', 'payment_methods',
        'seo_metas', 'settings', 'album_images', 'albums', 'videos'];

    /** Setting keys that hold mail server credentials; they belong in .env, never in the database. */
    private const SECRET_SETTINGS = ['mailertype', 'ip_host', 'port_host', 'secure_host', 'email_host', 'password_host', 'host_gmail', 'port_gmail', 'secure_gmail', 'email_gmail',
        'password_gmail', 'ip_host_as', 'email_host_as', 'password_host_as', 'email_as', 'apdev_as'];

    /** Small blocks kept in table_news by type => page section key. */
    private const NEWS_SECTIONS = [
        'cam-ket' => 'product.commitments', 'cam-ket-dai-ly' => 'dealer.benefits', 'phan-hoi-dai-ly' => 'dealer.testimonials', 'so-lieu-thong-ke-dai-ly' => 'dealer.stats',
        'quy-trinh' => 'home.process', 'lich-su' => 'about.history', 'su-menh' => 'about.compass', 'ly-do-chon-chung-toi' => 'about.reasons', 'about-team' => 'about.team',
        'about-principle' => 'about.principles', 'home-about' => 'home.about', 'home-about-point' => 'home.about_points', 'home-energy-chain' => 'home.energy_chain',
        'catalog-benefit' => 'catalog.benefits', 'quick-search' => 'search.suggestions', 'news-intro' => 'news.intro', 'news-cta' => 'news.cta', 'project-cta' => 'projects.cta',
        'project-trust' => 'projects.trust', 'project-intro' => 'projects.intro', 'project-benefit' => 'projects.benefits', 'solution-intro' => 'solutions.intro', 'solution-cta' => 'solutions.cta',
        'loi-ich' => 'archive.benefits', 'so-lieu-thong-ke' => 'archive.stats', 'tieu-chi' => 'archive.criteria',
    ];

    private const POST_KINDS = ['tin-tuc' => 'article', 'giai-phap' => 'solution', 'du-an' => 'project', 'chinh-sach' => 'policy'];

    /** Fixed page texts in table_static => page section key. */
    private const STATIC_SECTIONS = ['footer' => 'site.footer', 'lienhe' => 'contact.intro', 'dai-ly-1' => 'dealer.intro', 'dai-ly-2' => 'dealer.overview', 'dai-ly-3' => 'dealer.support',
        'tom-tat-gioi-thieu' => 'archive.about_summary', 'su-menh' => 'archive.mission', 'loi-ich' => 'archive.benefits_text'];

    public function handle(): int
    {
        $this->old = DB::connection('legacy');
        $this->new = DB::connection();
        $this->new->transaction(function () {
            if ($this->option('fresh')) {
                // TRUNCATE commits implicitly in MariaDB, so use DELETE to keep a failed import reversible.
                $this->new->statement('SET FOREIGN_KEY_CHECKS=0');
                try {
                    foreach (array_merge(self::NEW_TABLES, ['users']) as $table) {
                        $this->new->table($table)->delete();
                    }
                } finally {
                    $this->new->statement('SET FOREIGN_KEY_CHECKS=1');
                }
            }
            $this->users();
            $this->settings();
            $this->catalog();
            $this->content();
            $this->seo();
            $this->commerce();
        });
        @mkdir(storage_path('app'), 0775, true);
        file_put_contents(storage_path('app/import-map.json'), json_encode(['categories' => $this->categoryMap], JSON_PRETTY_PRINT));
        foreach (self::NEW_TABLES as $table) {
            $count = $this->new->table($table)->count();
            if ($count) {
                $this->line(sprintf('%-22s %d', $table, $count));
            }
        }

        return self::SUCCESS;
    }

    /**
     * Admin accounts keep their old password hash; App\\Auth\\AdminPasswords accepts it once and
     * replaces it with a bcrypt hash on the first sign-in.
     */
    private function users(): void
    {
        $permissions = [];
        foreach ($this->old->select('select id_permission_group as id_permission, permission from table_permission') as $row) {
            $permissions[(int) $row->id_permission][] = (string) $row->permission;
        }
        foreach ($this->old->table('table_user')->orderBy('id')->get() as $row) {
            $this->new->table('users')->updateOrInsert(['id' => $row->id], ['name' => self::text($row->fullname) ?: $row->username, 'username' => $row->username,
                'email' => self::nullable($row->email), 'phone' => self::nullable($row->phone), 'password' => $row->password, 'role' => (int) $row->role === 3 ? 'owner' : 'editor',
                'permissions' => json_encode(self::permissionAreas($permissions[(int) $row->id_permission] ?? [])), 'is_active' => self::visible($row->status), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function settings(): void
    {
        $row = (array) $this->old->table('table_setting')->orderBy('id')->first();
        $options = json_decode((string) ($row['options'] ?? ''), true) ?: [];
        $values = ['site_name' => self::text($row['namevi'] ?? '')];
        foreach ($options as $key => $value) {
            if (! in_array($key, self::SECRET_SETTINGS, true)) {
                $values[$key] = $value;
            }
        }
        foreach ($values as $key => $value) {
            $this->new->table('settings')->insert(['key' => $key, 'value' => json_encode($value, JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function catalog(): void
    {
        // Top categories keep their ids; deeper levels get ids after the highest top id.
        $next = (int) $this->old->table('table_product_list')->max('id') + 1;
        $levels = [['list', null], ['cat', 'id_list'], ['item', 'id_cat'], ['sub', 'id_item']];
        foreach ($levels as $depth => [$level, $parentColumn]) {
            foreach ($this->old->table('table_product_'.$level)->where('type', 'san-pham')->orderBy('id')->get() as $row) {
                $row = (array) $row;
                $id = $depth === 0 ? (int) $row['id'] : $next++;
                $this->categoryMap[$level.':'.$row['id']] = $id;
                $parent = $parentColumn ? ($this->categoryMap[$levels[$depth - 1][0].':'.$row[$parentColumn]] ?? null) : null;
                if ($depth > 0 && $parent === null) {
                    continue;
                }
                $flags = self::flags($row['status']);
                $this->new->table('categories')->insert(['id' => $id, 'parent_id' => $parent, 'depth' => $depth + 1, 'name' => self::text($row['namevi']), 'slug' => trim((string) $row['slugvi'], '/'),
                    'summary' => self::text($row['descvi']), 'body' => self::text($row['contentvi']), 'image' => self::nullable($row['photo']), 'pdp_profile' => self::nullable($row['pdp_profile'] ?? null),
                    'is_featured' => in_array('noibat', $flags, true), 'is_major' => in_array('major', $flags, true), 'in_solution_hub' => in_array('solutionhub', $flags, true),
                    'is_visible' => in_array('hienthi', $flags, true), 'sort_order' => (int) $row['numb']] + self::times($row));
            }
        }
        foreach ($this->old->table('table_product_brand')->where('type', 'san-pham')->orderBy('id')->get() as $row) {
            $row = (array) $row;
            $this->new->table('brands')->insert(['id' => $row['id'], 'name' => self::text($row['namevi']), 'slug' => trim((string) $row['slugvi'], '/'), 'summary' => self::text($row['descvi']),
                'body' => self::text($row['contentvi']), 'image' => self::nullable($row['photo']), 'is_visible' => self::visible($row['status']), 'sort_order' => (int) $row['numb']] + self::times($row));
        }
        $specColumns = ['battery_capacity', 'panel_power', 'light_source', 'lamp_dimensions', 'color_temperature', 'light_color', 'material', 'charging_time', 'lighting_time', 'waterproof_rating',
            'led_chip', 'lighting_area', 'installation_height', 'panel_dimensions', 'carton_quantity', 'carton_dimensions', 'weight', 'warranty'];
        $brandIds = $this->new->table('brands')->pluck('id')->all();
        foreach ($this->old->table('table_product')->where('type', 'san-pham')->orderBy('id')->get() as $row) {
            $row = (array) $row;
            $category = null;
            foreach (['sub' => 'id_sub', 'item' => 'id_item', 'cat' => 'id_cat', 'list' => 'id_list'] as $level => $column) {
                if ((int) $row[$column] > 0 && isset($this->categoryMap[$level.':'.$row[$column]])) {
                    $category = $this->categoryMap[$level.':'.$row[$column]];
                    break;
                }
            }
            $flags = self::flags($row['status']);
            $additional = json_decode(htmlspecialchars_decode((string) $row['additional_specs'], ENT_QUOTES), true);
            $product = ['id' => $row['id'], 'category_id' => $category, 'brand_id' => in_array((int) $row['id_brand'], $brandIds, true) ? (int) $row['id_brand'] : null,
                'name' => self::text($row['namevi']), 'slug' => trim((string) $row['slugvi'], '/'), 'code' => self::nullable(self::text($row['code'])), 'summary' => self::text($row['descvi']),
                'body' => self::text($row['contentvi']), 'highlights' => self::text($row['highlightsvi']), 'applications' => self::text($row['applicationsvi']),
                'installation' => self::text($row['installationvi']), 'specs_html' => self::text($row['thongsokythuatvi']), 'purchase_policy' => self::text($row['chinhsachmuahangvi']),
                'price_range' => self::nullable(self::text($row['khoanggia'])), 'regular_price' => (float) $row['regular_price'] > 0 ? round((float) $row['regular_price']) : null,
                'sale_price' => (float) $row['sale_price'] > 0 ? round((float) $row['sale_price']) : null, 'quote_only' => in_array('baogia', $flags, true),
                'image' => self::nullable($row['photo']), 'image_secondary' => self::nullable($row['photo2']), 'video_url' => self::nullable(self::text($row['video_url'])),
                'pdp_profile' => self::nullable($row['pdp_profile']), 'power_watts' => is_numeric(str_replace(',', '.', (string) $row['cong_suat'])) && (float) str_replace(',', '.', (string) $row['cong_suat']) != 0 ? (float) str_replace(',', '.', (string) $row['cong_suat']) : null,
                'package_contents' => self::nullable(self::text($row['package_contents'])), 'additional_specs' => is_array($additional) ? json_encode($additional, JSON_UNESCAPED_UNICODE) : null,
                'is_visible' => in_array('hienthi', $flags, true), 'is_bestseller' => in_array('banchay', $flags, true), 'sort_order' => (int) $row['numb'], 'views' => (int) $row['view']] + self::times($row);
            foreach ($specColumns as $column) {
                $product[$column] = self::nullable(self::text($row[$column]));
            }
            $this->new->table('products')->insert($product);
        }
        $productIds = array_flip($this->new->table('products')->pluck('id')->all());
        foreach ($this->old->table('table_gallery')->where('com', 'product')->where('kind', 'man')->where('val', 'san-pham')->orderBy('id')->get() as $row) {
            if (isset($productIds[$row->id_parent]) && trim((string) $row->photo) !== '') {
                $this->new->table('product_images')->insert(['id' => $row->id, 'product_id' => $row->id_parent, 'path' => $row->photo, 'caption' => self::nullable(self::text($row->namevi)),
                    'is_visible' => self::visible($row->status), 'sort_order' => (int) $row->numb] + self::times((array) $row));
            }
        }
        foreach ($this->old->table('table_product_document')->orderBy('id')->get() as $row) {
            if (isset($productIds[$row->id_product])) {
                $this->new->table('product_documents')->insert(['id' => $row->id, 'product_id' => $row->id_product, 'kind' => $row->document_type, 'title' => self::text($row->titlevi), 'path' => $row->file_path,
                    'is_visible' => self::visible($row->status), 'sort_order' => (int) $row->numb] + self::times((array) $row));
            }
        }
        foreach (['color' => 'table_color', 'size' => 'table_size'] as $kind => $table) {
            foreach ($this->old->table($table)->orderBy('id')->get() as $row) {
                $this->new->table('product_options')->insert(['kind' => $kind, 'name' => self::text($row->namevi), 'value' => $kind === 'color' ? ($row->color ?? null) : null,
                    'is_visible' => self::visible($row->status), 'sort_order' => (int) $row->numb, 'legacy_id' => $row->id] + self::times((array) $row));
            }
        }
        $owners = ['product' => ['App\\Models\\Product', fn ($id) => isset($productIds[$id]) ? $id : null], 'brand' => ['App\\Models\\Brand', fn ($id) => in_array((int) $id, $brandIds, true) ? $id : null],
            'list' => ['App\\Models\\Category', fn ($id) => $this->categoryMap['list:'.$id] ?? null], 'cat' => ['App\\Models\\Category', fn ($id) => $this->categoryMap['cat:'.$id] ?? null]];
        foreach ($this->old->table('table_content_section')->orderBy('id')->get() as $row) {
            [$type, $resolve] = $owners[$row->scope] ?? [null, null];
            $owner = $type ? $resolve($row->scope_id) : null;
            if ($owner === null) {
                continue;
            }
            $this->new->table('content_sections')->insert(['owner_type' => $type, 'owner_id' => $owner, 'mode' => $row->mode ?: 'own', 'inherit_source' => self::nullable($row->inherit_source),
                'blocks' => json_encode(ContentBlocks::decode($row->blocksvi), JSON_UNESCAPED_UNICODE), 'is_visible' => self::visible($row->status), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function content(): void
    {
        foreach ($this->old->table('table_news_list')->orderBy('id')->get() as $row) {
            if (! isset(self::POST_KINDS[$row->type])) {
                continue;
            }
            $this->new->table('post_categories')->insert(['id' => $row->id, 'kind' => self::POST_KINDS[$row->type], 'depth' => 1, 'name' => self::text($row->namevi), 'slug' => trim((string) $row->slugvi, '/'),
                'summary' => self::text($row->descvi), 'body' => self::text($row->contentvi), 'image' => self::nullable($row->photo), 'is_visible' => self::visible($row->status), 'sort_order' => (int) $row->numb] + self::times((array) $row));
        }
        $postCategories = array_flip($this->new->table('post_categories')->pluck('id')->all());
        $productIds = array_flip($this->new->table('products')->pluck('id')->all());
        foreach ($this->old->table('table_news')->orderBy('id')->get() as $row) {
            $row = (array) $row;
            $flags = self::flags($row['status']);
            if (isset(self::POST_KINDS[$row['type']])) {
                $this->new->table('posts')->insert(['id' => $row['id'], 'kind' => self::POST_KINDS[$row['type']], 'category_id' => isset($postCategories[$row['id_list']]) ? $row['id_list'] : null,
                    'author_id' => (int) $row['id_user'] ?: null, 'title' => self::text($row['namevi']), 'nav_title' => self::nullable(self::text($row['title_indexvi'])), 'slug' => trim((string) $row['slugvi'], '/'),
                    'summary' => self::text($row['descvi']), 'body' => self::text($row['contentvi']), 'extra_body' => self::text($row['content1vi']), 'image' => self::nullable($row['photo']),
                    'cover_image' => self::nullable($row['photo1']), 'is_visible' => in_array('hienthi', $flags, true), 'is_featured' => in_array('noibat', $flags, true),
                    'in_hub' => (bool) array_intersect(['solutionhub', 'newshub', 'projecthub'], $flags), 'client_name' => self::nullable(self::text($row['tenkhachhang'])),
                    'address' => self::nullable(self::text($row['address'])), 'started_on' => self::date($row['project_start_date']), 'finished_on' => self::date($row['project_end_date']),
                    'sort_order' => (int) $row['numb'], 'views' => (int) $row['view'], 'published_at' => (int) $row['date_created'] ?: null] + self::times($row));
            } elseif ($row['type'] === 'he-thong-cua-hang') {
                $this->new->table('store_locations')->insert(['id' => $row['id'], 'name' => self::text($row['namevi']), 'address' => self::text($row['address']), 'map_url' => self::nullable($row['ggmap']),
                    'map_embed' => self::nullable($row['map_iframe']), 'is_visible' => in_array('hienthi', $flags, true), 'sort_order' => (int) $row['numb']] + self::times($row));
            } elseif ($row['type'] === 'hinh-thuc-thanh-toan') {
                $this->new->table('payment_methods')->insert(['id' => $row['id'], 'name' => self::text($row['namevi']), 'description' => self::text($row['descvi']), 'slug' => self::nullable($row['slugvi']),
                    'is_visible' => in_array('hienthi', $flags, true), 'sort_order' => (int) $row['numb']] + self::times($row));
            } elseif (isset(self::NEWS_SECTIONS[$row['type']])) {
                $this->new->table('page_items')->insert(['id' => $row['id'], 'section' => self::NEWS_SECTIONS[$row['type']], 'title' => self::nullable(self::text($row['namevi'])),
                    'subtitle' => self::nullable(self::text($row['title_indexvi'])), 'summary' => self::text($row['descvi']), 'body' => self::text($row['contentvi']), 'extra' => self::text($row['content1vi']),
                    'figure' => self::nullable(self::text($row['solieu'])), 'image' => self::nullable($row['photo']), 'flags' => json_encode(array_values(array_diff($flags, ['hienthi']))),
                    'is_visible' => in_array('hienthi', $flags, true), 'sort_order' => (int) $row['numb']] + self::times($row));
            }
        }
        $postIds = array_flip($this->new->table('posts')->pluck('id')->all());
        // Post galleries: pictures of the post's own type and video links. "thongtinphu" rows were never shown on the site.
        foreach ($this->old->table('table_gallery')->where('com', 'news')->where('kind', 'man')->whereColumn('val', 'type')->orWhere(fn ($q) => $q->where('com', 'news')->where('kind', 'man')->where('val', 'video'))->orderBy('id')->get() as $row) {
            if (isset($postIds[$row->id_parent])) {
                $video = $row->val === 'video';
                $this->new->table('post_media')->insert(['id' => $row->id, 'post_id' => $row->id_parent, 'kind' => $video ? 'video' : 'image', 'path' => self::nullable($row->photo),
                    'video_url' => $video ? self::nullable($row->link_video) : null, 'caption' => self::nullable(self::text($row->namevi)), 'is_visible' => self::visible($row->status), 'sort_order' => (int) $row->numb] + self::times((array) $row));
            }
        }
        foreach ($this->old->table('table_product_project')->orderBy('id')->get() as $row) {
            if (isset($postIds[$row->id_project], $productIds[$row->id_product])) {
                $this->new->table('post_products')->insert(['post_id' => $row->id_project, 'product_id' => $row->id_product, 'is_visible' => self::visible($row->status), 'sort_order' => (int) $row->numb]);
            }
        }
        $nextItem = (int) $this->new->table('page_items')->max('id') + 1;
        foreach ($this->old->table('table_static')->orderBy('id')->get() as $row) {
            if (! isset(self::STATIC_SECTIONS[$row->type])) {
                continue;
            }
            $this->new->table('page_items')->insert(['id' => $nextItem++, 'section' => self::STATIC_SECTIONS[$row->type], 'title' => self::nullable(self::text($row->namevi)), 'summary' => self::text($row->descvi),
                'body' => self::text($row->contentvi), 'image' => self::nullable($row->photo), 'flags' => '[]', 'is_visible' => self::visible($row->status), 'sort_order' => 0] + self::times((array) $row));
        }
        foreach ($this->old->table('table_photo')->orderBy('id')->get() as $row) {
            if ($row->type === 'video') {
                $this->new->table('videos')->insert(['id' => $row->id, 'title' => self::text($row->namevi), 'url' => (string) $row->link_video, 'is_visible' => self::visible($row->status), 'sort_order' => (int) $row->numb] + self::times((array) $row));

                continue;
            }
            $flags = self::flags($row->status);
            $options = json_decode((string) $row->options, true);
            $this->new->table('banners')->insert(['id' => $row->id, 'placement' => $row->type, 'title' => self::nullable(self::text($row->namevi)), 'summary' => self::text($row->descvi),
                'image' => self::nullable($row->photo), 'link' => self::nullable($row->link), 'video_url' => self::nullable($row->link_video), 'options' => is_array($options) ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
                'is_visible' => in_array('hienthi', $flags, true), 'is_featured' => in_array('noibat', $flags, true), 'sort_order' => (int) $row->numb] + self::times((array) $row));
        }
        foreach ($this->old->table('table_product')->where('type', 'thu-vien-anh')->orderBy('id')->get() as $row) {
            $this->new->table('albums')->insert(['id' => $row->id, 'title' => self::text($row->namevi), 'slug' => trim((string) $row->slugvi, '/'), 'summary' => self::text($row->descvi), 'image' => self::nullable($row->photo),
                'is_visible' => self::visible($row->status), 'sort_order' => (int) $row->numb] + self::times((array) $row));
            foreach ($this->old->table('table_gallery')->where('id_parent', $row->id)->where('com', 'product')->where('val', 'thu-vien-anh')->get() as $image) {
                $this->new->table('album_images')->insert(['id' => $image->id, 'album_id' => $row->id, 'path' => $image->photo, 'caption' => self::nullable(self::text($image->namevi)),
                    'is_visible' => self::visible($image->status), 'sort_order' => (int) $image->numb] + self::times((array) $image));
            }
        }
    }

    private function seo(): void
    {
        $owners = ['product/man' => 'App\\Models\\Product', 'product/man_brand' => 'App\\Models\\Brand', 'product/man_list' => 'App\\Models\\Category', 'product/man_cat' => 'App\\Models\\Category',
            'news/man' => 'App\\Models\\Post', 'news/man_list' => 'App\\Models\\PostCategory'];
        $exists = ['App\\Models\\Album' => 'albums', 'App\\Models\\Product' => 'products', 'App\\Models\\Brand' => 'brands', 'App\\Models\\Category' => 'categories', 'App\\Models\\Post' => 'posts', 'App\\Models\\PostCategory' => 'post_categories'];
        foreach ($this->old->table('table_seo')->orderBy('id')->get() as $row) {
            $values = ['title' => self::nullable(self::text($row->titlevi)), 'description' => self::nullable(self::text($row->descriptionvi)), 'keywords' => self::nullable(self::text($row->keywordsvi)), 'created_at' => now(), 'updated_at' => now()];
            if ($row->com === 'setting') {
                $this->new->table('seo_metas')->insert(['page' => 'home'] + $values);

                continue;
            }
            $type = $row->com.'/'.$row->act === 'product/man' && $row->type === 'thu-vien-anh' ? 'App\\Models\\Album' : ($owners[$row->com.'/'.$row->act] ?? null);
            if ($type === null || ! in_array($row->type, ['san-pham', 'tin-tuc', 'giai-phap', 'du-an', 'chinh-sach', 'thu-vien-anh'], true)) {
                continue;
            }
            $id = match ($row->com.'/'.$row->act) {
                'product/man_list' => $this->categoryMap['list:'.$row->id_parent] ?? null,
                'product/man_cat' => $this->categoryMap['cat:'.$row->id_parent] ?? null,
                default => (int) $row->id_parent,
            };
            if ($id && $this->new->table($exists[$type])->where('id', $id)->exists() && ! $this->new->table('seo_metas')->where('owner_type', $type)->where('owner_id', $id)->exists()) {
                $this->new->table('seo_metas')->insert(['owner_type' => $type, 'owner_id' => $id] + $values);
            }
        }
        foreach ($this->old->table('table_seopage')->orderBy('id')->get() as $row) {
            $this->new->table('seo_metas')->insert(['page' => $row->type, 'title' => self::nullable(self::text($row->titlevi)), 'description' => self::nullable(self::text($row->descriptionvi)),
                'keywords' => self::nullable(self::text($row->keywordsvi)), 'image' => self::nullable($row->photo), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function commerce(): void
    {
        foreach ($this->old->table('table_city')->orderBy('id')->get() as $row) {
            $this->new->table('provinces')->insert(['id' => $row->id, 'name' => $row->name, 'code' => self::nullable($row->code), 'is_visible' => self::visible($row->status), 'sort_order' => (int) $row->numb] + self::times((array) $row));
        }
        foreach ($this->old->table('table_ward')->where('id_district', 0)->orderBy('id')->lazy(2000) as $row) {
            $this->new->table('wards')->insert(['id' => $row->id, 'province_id' => $row->id_city, 'name' => $row->name, 'code' => self::nullable($row->code), 'shipping_fee' => max(0, (float) $row->ship_price),
                'is_visible' => self::visible($row->status), 'sort_order' => (int) $row->numb] + self::times((array) $row));
        }
        $productIds = array_flip($this->new->table('products')->pluck('id')->all());
        $paymentIds = array_flip($this->new->table('payment_methods')->pluck('id')->all());
        foreach ($this->old->table('table_order')->orderBy('id')->get() as $row) {
            $this->new->table('orders')->insert(['id' => $row->id, 'code' => $row->code, 'checkout_token' => self::nullable($row->checkout_token), 'customer_name' => self::text($row->fullname), 'phone' => self::text($row->phone),
                'email' => self::nullable(self::text($row->email)), 'address' => self::text($row->address), 'province_id' => (int) $row->city ?: null, 'ward_id' => (int) $row->ward ?: null,
                'payment_method_id' => isset($paymentIds[$row->order_payment]) ? $row->order_payment : null, 'payment_code' => $row->payment_method_code ?: 'manual',
                'payment_status' => (int) $row->payment_status === 1 ? 'paid' : 'unpaid', 'payment_reference' => self::nullable($row->payment_transaction_ref), 'subtotal' => round((float) $row->temp_price),
                'shipping_fee' => round((float) $row->ship_price), 'total' => round((float) $row->total_price), 'requirements' => self::text($row->requirements), 'notes' => self::text($row->notes),
                'status' => [1 => 'new', 2 => 'confirmed', 3 => 'shipping', 4 => 'done', 5 => 'cancelled'][(int) $row->order_status] ?? 'new',
                'created_at' => date('Y-m-d H:i:s', (int) $row->date_created ?: time()), 'updated_at' => date('Y-m-d H:i:s', (int) $row->date_created ?: time())]);
            foreach ($this->old->table('table_order_detail')->where('id_order', $row->id)->get() as $item) {
                $this->new->table('order_items')->insert(['order_id' => $row->id, 'product_id' => isset($productIds[$item->id_product]) ? $item->id_product : null, 'name' => self::text($item->name), 'image' => self::nullable($item->photo),
                    'color' => self::nullable($item->color), 'size' => self::nullable($item->size), 'regular_price' => $item->regular_price, 'sale_price' => $item->sale_price, 'quantity' => $item->quantity, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        foreach ($this->old->table('table_comment')->where('type', 'san-pham')->orderBy('id_parent')->orderBy('id')->get() as $row) {
            if (! isset($productIds[$row->id_variant])) {
                continue;
            }
            $flags = self::flags($row->status);
            $this->new->table('reviews')->insert(['id' => $row->id, 'product_id' => $row->id_variant, 'parent_id' => (int) $row->id_parent ?: null, 'stars' => (int) $row->star ?: null, 'title' => self::nullable(self::text($row->title)),
                'content' => self::text($row->content), 'name' => self::text($row->fullname), 'phone' => self::nullable($row->phone), 'email' => self::nullable($row->email), 'by_staff' => $row->poster === 'admin',
                'verified_purchase' => in_array('verified-purchase', $flags, true), 'status' => in_array('hienthi', $flags, true) ? 'approved' : 'pending',
                'created_at' => date('Y-m-d H:i:s', (int) $row->date_posted), 'updated_at' => date('Y-m-d H:i:s', (int) $row->date_posted)]);
        }
        $reviewIds = array_flip($this->new->table('reviews')->pluck('id')->all());
        foreach ($this->old->table('table_comment_photo')->get() as $row) {
            if (isset($reviewIds[$row->id_parent])) {
                $this->new->table('review_media')->insert(['review_id' => $row->id_parent, 'kind' => 'photo', 'path' => $row->photo, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        foreach ($this->old->table('table_comment_video')->get() as $row) {
            if (isset($reviewIds[$row->id_parent])) {
                $this->new->table('review_media')->insert(['review_id' => $row->id_parent, 'kind' => 'video', 'path' => $row->video, 'poster' => self::nullable($row->photo), 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        foreach ($this->old->table('table_contact')->orderBy('id')->get() as $row) {
            $this->new->table('leads')->insert(['kind' => 'contact', 'name' => self::text($row->fullname), 'phone' => self::text($row->phone), 'email' => self::nullable(self::text($row->email)),
                'subject' => self::nullable(self::text($row->subject)), 'message' => self::text($row->content), 'address' => self::nullable(self::text($row->address)), 'notes' => self::text($row->notes),
                'created_at' => date('Y-m-d H:i:s', (int) $row->date_created), 'updated_at' => date('Y-m-d H:i:s', (int) $row->date_created)]);
        }
        foreach ($this->old->table('table_newsletter')->orderBy('id')->get() as $row) {
            $this->new->table('leads')->insert(['kind' => $row->type === 'dangkynhantin2' ? 'dealer' : 'consultation', 'name' => self::text($row->fullname), 'phone' => self::text($row->phone),
                'email' => self::nullable(self::text($row->email)), 'company' => self::nullable(self::text($row->company)), 'message' => self::text($row->details ?? $row->content), 'address' => self::nullable(self::text($row->address)),
                'province_id' => (int) $row->city_id ?: null, 'ward_id' => (int) $row->ward_id ?: null, 'product_id' => isset($productIds[$row->product_id]) ? $row->product_id : null,
                'profile_key' => self::nullable($row->profile_key), 'need_code' => self::nullable($row->need_code), 'audience' => self::nullable(self::text($row->loaihinh)), 'quantity' => self::nullable(self::text($row->congsuat)),
                'notes' => self::text($row->notes), 'created_at' => date('Y-m-d H:i:s', (int) $row->date_created), 'updated_at' => date('Y-m-d H:i:s', (int) $row->date_created)]);
        }
    }

    /** The PHP Admin saved most text through htmlspecialchars(); decode that one layer. */
    private static function text(mixed $value): string
    {
        return is_scalar($value) ? htmlspecialchars_decode((string) $value, ENT_QUOTES) : '';
    }

    private static function nullable(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    /** @return list<string> */
    private static function flags(mixed $status): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $status))));
    }

    private static function visible(mixed $status): bool
    {
        return in_array('hienthi', self::flags($status), true);
    }

    private static function date(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return preg_match('/^\d{4}-\d{2}-\d{2}/', $value) && ! str_starts_with($value, '0000') ? substr($value, 0, 10) : null;
    }

    /** @return array{created_at: string, updated_at: string} */
    private static function times(array $row): array
    {
        $created = (int) ($row['date_created'] ?? 0) ?: time();
        $updated = (int) ($row['date_updated'] ?? 0) ?: $created;

        return ['created_at' => date('Y-m-d H:i:s', $created), 'updated_at' => date('Y-m-d H:i:s', $updated)];
    }

    /**
     * Old per-screen permission keys ("product_edit_list_san-pham", "news_man_tin-tuc"…) become the
     * new areas. Gallery and video screens lived under product/news keys with their own types.
     *
     * @param  list<string>  $keys
     * @return list<string>
     */
    private static function permissionAreas(array $keys): array
    {
        $areas = [];
        foreach ($keys as $key) {
            $area = match (true) {
                str_contains($key, 'thu-vien-anh') || str_contains($key, '_video') || str_starts_with($key, 'photo_') => 'media',
                str_starts_with($key, 'product_') || str_starts_with($key, 'content_section_') => 'catalog',
                str_starts_with($key, 'comment_') => 'reviews',
                str_starts_with($key, 'contact_') || str_starts_with($key, 'newsletter_') => 'leads',
                str_starts_with($key, 'order_') || str_starts_with($key, 'places_') || str_contains($key, 'hinh-thuc-thanh-toan') => 'orders',
                str_starts_with($key, 'setting_') || str_starts_with($key, 'seopage_') || str_starts_with($key, 'static_') => 'settings',
                str_starts_with($key, 'news_') => 'content',
                default => null,
            };
            if ($area !== null) {
                $areas[$area] = true;
            }
        }

        return array_keys($areas);
    }
}
