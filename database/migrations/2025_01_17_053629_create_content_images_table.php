<?php

use Illuminate\Database\Migrations\Migration;  
use Illuminate\Database\Schema\Blueprint;  
use Illuminate\Support\Facades\Schema;  
  
class CreateContentImagesTable extends Migration  
{  
    public function up()  
    {  
        Schema::create('content_images', function (Blueprint $table) {  
            $table->id();  
            $table->foreignId('story_id')->constrained()->onDelete('cascade');  
            $table->string('path');  
            $table->timestamps();  
        });  
    }  
  
    public function down()  
    {  
        Schema::dropIfExists('content_images');  
    }  
};
