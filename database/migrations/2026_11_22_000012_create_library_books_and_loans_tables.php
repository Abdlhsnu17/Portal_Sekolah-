<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('library_books',function(Blueprint $t){$t->id();$t->string('code',50)->unique();$t->string('title',200);$t->string('author',150)->nullable();$t->string('publisher',150)->nullable();$t->unsignedInteger('stock')->default(0);$t->timestamps();});Schema::create('library_loans',function(Blueprint $t){$t->id();$t->foreignId('library_book_id')->constrained()->cascadeOnDelete();$t->foreignId('student_id')->constrained()->cascadeOnDelete();$t->date('borrowed_on');$t->date('due_on')->nullable();$t->date('returned_on')->nullable();$t->decimal('fine',12,2)->default(0);$t->timestamps();$t->index(['student_id','returned_on']);});} public function down():void{Schema::dropIfExists('library_loans');Schema::dropIfExists('library_books');} };
