<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTranslationjobTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('translationjobs', function (Blueprint $table) {
            $table->id();

            $table->text('title');
            $table->text('notes')->nullable();
            $table->enum('status', ['new', 'pending', 'reviewing', 'done']);
            $table->json('jobcontents');
            $table->json('xlatprovider');
            $table->json('history')->nullable();
            
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
        Schema::dropIfExists('translationjobs');
    }
}
