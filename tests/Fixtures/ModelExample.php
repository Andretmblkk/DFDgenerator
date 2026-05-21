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
