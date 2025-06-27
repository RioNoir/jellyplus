<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->increments('item_id');
            $table->string('item_md5')->nullable();
            $table->string('item_type')->nullable();
            $table->string('item_jellyfin_id')->nullable();
            $table->string('item_imdb_id')->nullable();
            $table->string('item_tmdb_id')->nullable();
            $table->string('item_jw_id')->nullable();
            $table->string('item_title')->nullable();
            $table->string('item_original_title')->nullable();
            $table->string('item_year')->nullable();
            $table->string('item_image_url')->nullable();
            $table->string('item_image_md5')->nullable();
            $table->string('item_path')->nullable();
            $table->string('item_server_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('items');
    }
};
