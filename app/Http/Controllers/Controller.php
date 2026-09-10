<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * 全コントローラの基底クラス。
 *
 * Laravel 11 以降のスリム構成では認可トレイトが外れているので、
 * $this->authorize() / authorizeResource() を使えるよう AuthorizesRequests を足しておく。
 */
abstract class Controller
{
    use AuthorizesRequests;
}
