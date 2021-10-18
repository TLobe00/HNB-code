<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration auto-generated by Sequel Pro/Ace Laravel Export (1.8.1)
 * @see https://github.com/cviebrock/sequel-pro-laravel-export
 */
class CreateScrequestNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('screquest_notes', function (Blueprint $table) {
            $table->increments('id');
            $table->text('description')->nullable();
            $table->string('type', 255)->nullable();
            $table->integer('noteID')->nullable();
            $table->dateTime('date')->nullable();
            $table->string('actUser', 255)->nullable();
            $table->string('isPublic', 255)->nullable();
            $table->integer('requestID')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->integer('screquest_id')->nullable();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_bin';
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('screquest_notes');
    }
}
