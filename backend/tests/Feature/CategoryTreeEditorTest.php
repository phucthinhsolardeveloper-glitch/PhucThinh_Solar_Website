<?php

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\CategoryTreeEditor;
use App\Domain\Routing\Redirects;
use App\Filament\Resources\Categories\Pages\ManageCategoryTree;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/** The tree as stored now, in the shape the drag-and-drop page sends. */
function currentTree(int $parent = 0): array
{
    $tree = new CategoryTree;

    return array_map(fn ($node) => ['id' => (int) $node['id'], 'children' => currentTree((int) $node['id'])], $tree->children($parent));
}

function moveNode(array $tree, int $id, int $newParent): array
{
    $taken = null;
    $strip = function (array $nodes) use (&$strip, &$taken, $id) {
        $out = [];
        foreach ($nodes as $node) {
            if ($node['id'] === $id) {
                $taken = $node;

                continue;
            }
            $node['children'] = $strip($node['children']);
            $out[] = $node;
        }

        return $out;
    };
    $tree = $strip($tree);
    $add = function (array $nodes) use (&$add, $taken, $newParent) {
        foreach ($nodes as &$node) {
            if ($node['id'] === $newParent) {
                $node['children'][] = $taken;
            }
            $node['children'] = $add($node['children']);
        }

        return $nodes;
    };

    return $add($tree);
}

it('saves the same tree as no change, then a moved branch with depths, order and a 301', function () {
    $editor = app(CategoryTreeEditor::class);
    $editor->apply(currentTree(), $editor->version()); // first save fixes tie orders; the next identical save changes nothing
    expect($editor->apply(currentTree(), $editor->version()))->toBe(['moved' => [], 'reordered' => 0]);

    $roots = DB::table('categories')->whereNull('parent_id')->orderBy('id')->pluck('id')->all();
    $child = (int) DB::table('categories')->where('parent_id', $roots[0])->value('id');
    $from = '/'.trim((new CategoryTree)->path($child), '/').'/';
    $result = $editor->apply(moveNode(currentTree(), $child, (int) $roots[1]), $editor->version());

    $row = DB::table('categories')->where('id', $child)->first();
    $to = '/'.trim((new CategoryTree)->path($child), '/').'/';
    expect($result['moved'])->toHaveCount(1)
        ->and((int) $row->parent_id)->toBe((int) $roots[1])
        ->and((int) $row->depth)->toBe(2)
        ->and(Redirects::find($from))->toBe($to);
});

it('refuses a fifth level and a tree that does not match the stored one', function () {
    $editor = app(CategoryTreeEditor::class);
    $tree = currentTree();
    $chain = ['id' => $tree[0]['id'], 'children' => []];
    // Nest five existing top categories into one chain.
    $ids = array_slice(array_column($tree, 'id'), 0, 5);
    $nested = [];
    foreach (array_reverse($ids) as $id) {
        $nested = [['id' => $id, 'children' => $nested]];
    }
    $rest = array_values(array_filter($tree, fn ($node) => ! in_array($node['id'], $ids, true)));
    expect(fn () => $editor->apply(array_merge($nested, $rest), $editor->version()))->toThrow(InvalidArgumentException::class, '4 cấp')
        ->and(fn () => $editor->apply(array_slice($tree, 1), $editor->version()))->toThrow(InvalidArgumentException::class);
});

it('rejects a stale tree instead of overwriting a category changed in another tab', function () {
    $editor = app(CategoryTreeEditor::class);
    $version = $editor->version();
    $id = (int) DB::table('categories')->whereNull('parent_id')->value('id');
    DB::table('categories')->where('id', $id)->update(['sort_order' => 765432]);

    expect(fn () => $editor->apply(currentTree(), $version))->toThrow(InvalidArgumentException::class, 'đã thay đổi ở nơi khác')
        ->and((int) DB::table('categories')->where('id', $id)->value('sort_order'))->toBe(765432);
});

it('refuses to reuse old URLs when two equal slugs swap parents', function () {
    $editor = app(CategoryTreeEditor::class);
    [$first, $second] = DB::table('categories')->whereNull('parent_id')->orderBy('id')->limit(2)->pluck('id')->all();
    $create = fn (int $parent, string $name) => DB::table('categories')->insertGetId([
        'parent_id' => $parent, 'depth' => 2, 'slug' => 'pts-tree-swap', 'name' => $name,
        'sort_order' => 9999, 'is_visible' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $left = $create((int) $first, 'Swap left');
    $right = $create((int) $second, 'Swap right');
    $tree = moveNode(currentTree(), $left, (int) $second);
    $tree = moveNode($tree, $right, (int) $first);

    expect(fn () => $editor->apply($tree, $editor->version()))->toThrow(InvalidArgumentException::class, 'link cũ');

    expect((int) DB::table('categories')->where('id', $left)->value('parent_id'))->toBe((int) $first)
        ->and((int) DB::table('categories')->where('id', $right)->value('parent_id'))->toBe((int) $second)
        ->and(DB::table('categories')->whereIn('id', [$left, $right])->pluck('slug')->all())->toBe(['pts-tree-swap', 'pts-tree-swap']);
});

it('keeps the destination URL and picks a free suffix when a moved slug collides twice', function () {
    $editor = app(CategoryTreeEditor::class);
    [$source, $target] = DB::table('categories')->whereNull('parent_id')->orderBy('id')->limit(2)->pluck('id')->all();
    $create = fn (int $parent, string $slug) => DB::table('categories')->insertGetId([
        'parent_id' => $parent, 'depth' => 2, 'slug' => $slug, 'name' => $slug,
        'sort_order' => 9999, 'is_visible' => false, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $incoming = $create((int) $source, 'pts-tree-collision');
    $existing = $create((int) $target, 'pts-tree-collision');
    $reserved = $create((int) $target, 'pts-tree-collision-'.$incoming);

    $editor->apply(moveNode(currentTree(), $incoming, (int) $target), $editor->version());

    expect(DB::table('categories')->where('id', $existing)->value('slug'))->toBe('pts-tree-collision')
        ->and(DB::table('categories')->where('id', $reserved)->value('slug'))->toBe('pts-tree-collision-'.$incoming)
        ->and(DB::table('categories')->where('id', $incoming)->value('slug'))->toBe('pts-tree-collision-'.$incoming.'-2');
});

it('shows the tree page to catalogue staff and saves through it', function () {
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'chu-'.bin2hex(random_bytes(3)), 'password' => 'test-password', 'role' => User::ROLE_OWNER, 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $name = (string) DB::table('categories')->whereNull('parent_id')->value('name');
    Livewire::test(ManageCategoryTree::class)->assertSee($name)->assertSee('Lưu sắp xếp')
        ->call('saveTree', currentTree())->assertNotified()->assertRedirect();
});
