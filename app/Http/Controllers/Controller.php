<?php

namespace App\Http\Controllers;

use PretrashBarcode\BarcodeSearch;
use PretrashBarcode\BarcodeSearch\Providers\AbstractProvider;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use Illuminate\Http\Request;
use Mail;

use Entity\ProductList;
use Entity\Product;
use Entity\User;
use EntityManager;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;

    public function show()
    {
      $userId = Auth::id();
      $repository = app('em');
      $user = $repository->getRepository(User::class)->findOneBy(['id' => $userId]);
      $repository = $repository->getRepository(ProductList::class);
      $list = $repository->findOneBy(['user' => $user], ['id' => 'desc']);
      if(!$list) {
        $list = new ProductList($user);
        app('em')->persist($list);
        app('em')->flush();
      }
      return view('list', ['list' => $list, 'userId' => $user->getEmail()]);
    }

    public function add(Request $request)
    {
      $userId = Auth::id();
      $repository = app('em');
      $user = $repository->getRepository(User::class)->findOneBy(['id' => $userId]);
      $repository = $repository->getRepository(ProductList::class);
      $list = $repository->findOneBy(['id' => $request->input('list')], ['id' => 'desc']);
      $prod = app('em')->getRepository(Product::class)->findOneByBarcode($request->input('product'), $user);
      if(!$prod) {
        $search = new BarcodeSearch();
        if($name = $search->search($request->input('product'))) {
          $prod = new Product($request->input('product'), $name, $user);
          app('em')->persist($prod);
          app('em')->flush();
        } else {
          return response()->json(['Product not found'], 500);
        }
      }
      $list->addProduct($prod);
      app('em')->flush();
      if($request->server('HTTP_REFERER')) {
        return redirect($request->server('HTTP_REFERER'));
      }
      return '';
    }

    public function newList(Request $request)
    {
      $userId = Auth::id();
      $repository = app('em');
      $user = $repository->getRepository(User::class)->findOneBy(['id' => $userId]);
      $list = new ProductList($user);
      app('em')->persist($list);
      app('em')->flush();
    }

    public function send(Request $request)
    {
      $userId = Auth::id();
      $repository = app('em');
      $user = $repository->getRepository(User::class)->findOneBy(['id' => $userId]);
      $repository = app('em');
      $repository = $repository->getRepository(ProductList::class);
      $list = $repository->findOneBy(['id' => $request->input('list')], ['id' => 'desc']);
      $mail = $request->input('default-mail') ? $user->getEmail() : $request->input('mail');
      Mail::send('emails.list', ['list' => $list], function ($m) use ($mail) {
           $m->from('altsiviero@gmail.com', 'Andre Siviero');
           $m->to($mail, '')->subject('Shop List');
       });
       if($request->server('HTTP_REFERER')) {
         return redirect($request->server('HTTP_REFERER'));
       }
    }
}
