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
            $table->enum('status', ['new', 'dispatched', 'cancelled', 'done']);
            $table->json('xlatprovider');
            $table->json('history')->nullable();
            
            $table->timestamps();
        });

        Schema::create('translationjobbables', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('translationjob_id');
            $table->unsignedInteger('translationjobbable_id');
            $table->string('translationjobbable_type');

            $table->foreign('translationjob_id')
                  ->references('id')
                  ->on('translationjobs')
                  ->onDelete('cascade');

            $table->index([
                'translationjobbable_id',
                'translationjobbable_type'
            ]);

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
        Schema::dropIfExists('translationjobbables');
        Schema::dropIfExists('translationjobs');
    }
}
