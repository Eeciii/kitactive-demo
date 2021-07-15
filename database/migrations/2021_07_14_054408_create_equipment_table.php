<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEquipmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title'); # Наименование оборудования
            $table->integer('price'); # Стоимость
            $table->string('serial_number')->unique(); # Серийный номер
            $table->string('inventory_number')->unique(); # Инвентарный номер
//            $table->tinyInteger('status')->default(0); # Статус перемещения (0 - Заявка создана; 1 - Перемещено)
            $table->bigInteger('warehouse_id')->unsigned()->nullable(); # ID склада
            $table->foreign('warehouse_id')->references('id')->on('warehouses'); # Создание связи
            $table->bigInteger('user_id')->unsigned(); # ID пользователя
            $table->foreign('user_id')->references('id')->on('users'); # Связь между user_id и id
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
        Schema::dropIfExists('equipment');
    }
}
