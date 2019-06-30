<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('checklist_id')->unsigned();
            $table->foreign('checklist_id')
                ->references('id')
                ->on('checklists')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('description', 300);
            $table->dateTimeTz('due')->nullable();
            $table->integer('urgency')->default(0);
            $table->integer('assignee_id')->default(0);
            $table->integer('task_id')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->dateTimeTz('completed_at')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by');
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
}
