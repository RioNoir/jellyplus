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
        Schema::table('items', function (Blueprint $table) {
            $table->string("item_addon_id")->nullable()->after("item_id");
            $table->string("item_addon_meta_id")->nullable()->after("item_addon_id");
            $table->string("item_addon_meta_type")->nullable()->after("item_addon_meta_id");
            $table->longText("item_description")->nullable()->after("item_original_title");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn("item_addon_id");
            $table->dropColumn("item_addon_meta_id");
            $table->dropColumn("item_addon_meta_type");
            $table->dropColumn("item_description");
        });
    }
};
