<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileSyncMappingsTable extends Migration
{
    public function up()
    {
        Schema::create('mobile_sync_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_id')->unique();
            $table->unsignedBigInteger('server_id')->nullable()->index();
            $table->string('model')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_sync_mappings');
    }
}
