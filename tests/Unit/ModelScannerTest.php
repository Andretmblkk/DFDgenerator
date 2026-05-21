<?php

declare(strict_types=1);

namespace LaravelDfd\Tests\Unit;

use LaravelDfd\Scanner\ModelScanner;
use LaravelDfd\Tests\TestCase;

final class ModelScannerTest extends TestCase
{
    public function test_it_detects_model_operations_and_db_facade_usage(): void
    {
        $source = <<<'PHP'
        <?php

        use App\Models\Post;
        use App\Models\User;
        use Illuminate\Support\Facades\DB;

        $user = User::create(['name' => 'A']);
        $user->save();

        Post::where('published', true)->update(['featured' => true]);
        Post::find(1)->delete();

        $users = DB::table('users');
        $users->delete();

        DB::select('select * from users');
        DB::insert('insert into users (name) values (?)', ['A']);
        DB::update('update users set name = ?', ['B']);
        DB::delete('delete from users where id = ?', [1]);
        PHP;

        $result = (new ModelScanner())->scanSource($source);

        self::assertSame(['User', 'Post'], $result['models']);
        self::assertSame(['users'], $result['tables']);

        self::assertContains([
            'type' => 'model_create',
            'target' => 'User',
        ], $result['operations']);

        self::assertContains([
            'type' => 'model_save',
            'target' => 'User',
        ], $result['operations']);

        self::assertContains([
            'type' => 'model_where',
            'target' => 'Post',
        ], $result['operations']);

        self::assertContains([
            'type' => 'model_update',
            'target' => 'Post',
        ], $result['operations']);

        self::assertContains([
            'type' => 'model_find',
            'target' => 'Post',
        ], $result['operations']);

        self::assertContains([
            'type' => 'model_delete',
            'target' => 'Post',
        ], $result['operations']);

        self::assertContains([
            'type' => 'db_table',
            'target' => 'users',
        ], $result['operations']);

        self::assertContains([
            'type' => 'db_delete',
            'target' => 'users',
        ], $result['operations']);

        foreach (['select', 'insert', 'update', 'delete'] as $method) {
            self::assertContains([
                'type' => 'db_' . $method,
                'target' => null,
            ], $result['operations']);
        }
    }

    public function test_it_scans_php_source_files(): void
    {
        $result = (new ModelScanner())->scanFile(__DIR__ . '/../Fixtures/ModelExample.php');

        self::assertSame(['User', 'Post'], $result['models']);
        self::assertSame(['users'], $result['tables']);
        self::assertContains([
            'type' => 'model_create',
            'target' => 'User',
        ], $result['operations']);
    }
}
