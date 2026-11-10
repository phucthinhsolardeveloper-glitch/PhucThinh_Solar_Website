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
    $editor->apply(currentTree()); // first save fixes tie orders; the next identical save changes nothing
    expect($editor->apply(currentTree()))->toBe(['moved' => [], 'reordered' => 0]);

    $roots = DB::table('categories')->whereNull('parent_id')->orderBy('id')->pluck('id')->all();
    $child = (int) DB::table('categories')->where('parent_id', $roots[0])->value('id');
    $from = '/'.trim((new CategoryTree)->path($child), '/').'/';
    $result = $editor->apply(moveNode(currentTree(), $child, (int) $roots[1]));

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
    expect(fn () => $editor->apply(array_merge($nested, $rest)))->toThrow(InvalidArgumentException::class, '4 cấp')
        ->and(fn () => $editor->apply(array_slice($tree, 1)))->toThrow(InvalidArgumentException::class);
});

it('shows the tree page to catalogue staff and saves through it', function () {
    $owner = User::query()->create(['name' => 'Chủ', 'username' => 'chu-'.bin2hex(random_bytes(3)), 'password' => 'test-password', 'role' => User::ROLE_OWNER, 'is_active' => true]);
    $this->actingAs($owner, 'admin');
    $name = (string) DB::table('categories')->whereNull('parent_id')->value('name');
    Livewire::test(ManageCategoryTree::class)->assertSee($name)->assertSee('Lưu sắp xếp')
        ->call('saveTree', currentTree())->assertNotified()->assertRedirect();
});
