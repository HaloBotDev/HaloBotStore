<?php

class Users extends Illuminate\Database\Eloquent\Model {
  protected $table = 'users';

  public static function get_id($user) {
    $user = Users::where('email', '=', $user)->first();
    return $user->id;
  }
}
