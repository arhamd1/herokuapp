<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    function getMyHero(){
        DB::beginTransaction();

        $endpoint = "https://anime-facts-rest-api.herokuapp.com/api/v1";
        $client = new Client();

        try{
            $response = $client->request('GET', $endpoint);
            $statusCode = $response->getStatusCode();
            $content = json_decode($response->getBody(), true);

            if (!Schema::hasTable('movie')) {
                Schema::create('movie', function (Blueprint $table) {
                    $table->increments('anime_id');
                    $table->string('anime_name');
                    $table->text('anime_img');
                    $table->timestamps();
                });
            }

            foreach ($content['data'] as $data){
                $url_gambar = $data['anime_img'];
                $anime_name = $data['anime_name'];
                $id_gambar = $data['anime_id'];

                $anime_img = @file_get_contents($url_gambar);

                if($anime_img === false){
                    //kalo error lanjut, cuman gimana kebijakan apakah di cancel atau bagaimana
                    continue;
                }

                $path = public_path('anime_img/');
                $extention = substr($url_gambar, strrpos($url_gambar, '.') + 1);
                $namafile = $id_gambar.'.'.$extention;

                if (!File::exists($path)) {
                    File::makeDirectory($path, 0777, true, true);
                }

                File::put( public_path( 'anime_img/'.$namafile), $anime_img);

                DB::table('movie')->insert([
                    'anime_id' => $id_gambar,
                    'anime_name' => $anime_name,
                    'anime_img' => $url_gambar,
                    'created_at' => now()
                ]);
            }

            DB::commit();

            dd('Berhasil');
        }catch (Throwable $e){
            DB::rollback();
            echo 'gagal';
            dd($e);
        }

    }
}
