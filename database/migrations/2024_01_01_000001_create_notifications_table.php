<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Notifications Table Migration
 *
 * Creates the notifications table for the notification system.
 *
 * @package BraCalculator\App\Database\Migrations
 */
return new class extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up(): void {
		Schema::create( 'notifications', function ( Blueprint $table ) {
			$table->id();
			$table->unsignedBigInteger( 'user_id' )->nullable();
			$table->string( 'type' );
			$table->string( 'title' );
			$table->text( 'message' );
			$table->string( 'link' )->nullable();
			$table->boolean( 'read' )->default( false );
			$table->timestamp( 'read_at' )->nullable();
			$table->timestamps();

			// Indexes
			$table->index( 'user_id' );
			$table->index( 'type' );
			$table->index( 'read' );
		} );
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(): void {
		Schema::dropIfExists( 'notifications' );
	}
};
