<?php

namespace BraCalculator\App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use WPJarvis\Framework\Support\Facades\Event;

/**
 * Notification Model
 *
 * Represents a notification in the system.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string|null $link
 * @property bool $read
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @package BraCalculator\App\Models
 */
class Notification extends Model {
	use HasFactory;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'notifications';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'user_id',
		'type',
		'title',
		'message',
		'link',
		'read',
		'read_at',
	];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'read'    => 'boolean',
		'read_at' => 'datetime',
	];

	/**
	 * Notification types.
	 *
	 * @var array<string, string>
	 */
	public const TYPES = [
		'info'    => 'info',
		'success' => 'success',
		'warning' => 'warning',
		'error'   => 'error',
	];

	/**
	 * Get the user that owns the notification.
	 *
	 * @return BelongsTo
	 */
	public function user(): BelongsTo {
		return $this->belongsTo( \WP_User::class, 'user_id', 'ID' );
	}

	/**
	 * Scope a query to only include unread notifications.
	 *
	 * @param Builder $query
	 *
	 * @return Builder
	 */
	public function scopeUnread( Builder $query ): Builder {
		return $query->where( 'read', false );
	}

	/**
	 * Scope a query to only include read notifications.
	 *
	 * @param Builder $query
	 *
	 * @return Builder
	 */
	public function scopeRead( Builder $query ): Builder {
		return $query->where( 'read', true );
	}

	/**
	 * Scope a query to filter by type.
	 *
	 * @param Builder $query
	 * @param string $type
	 *
	 * @return Builder
	 */
	public function scopeOfType( Builder $query, string $type ): Builder {
		return $query->where( 'type', $type );
	}

	/**
	 * Mark the notification as read.
	 *
	 * @return bool
	 */
	public function markAsRead(): bool {
		return $this->update( [
			'read'    => true,
			'read_at' => Carbon::now(),
		] );
	}

	/**
	 * Mark the notification as unread.
	 *
	 * @return bool
	 */
	public function markAsUnread(): bool {
		return $this->update( [
			'read'    => false,
			'read_at' => null,
		] );
	}

	/**
	 * Create a new notification.
	 *
	 * @param array $attributes
	 *
	 * @return static
	 */
	public static function create( array $attributes = [] ): static {
		$notification = parent::create( $attributes );

		// Dispatch notification created event
		Event::dispatch( new \BraCalculator\App\Events\NotificationCreated( $notification ) );

		return $notification;
	}

	/**
	 * Get the notification type label.
	 *
	 * @return string
	 */
	public function getTypeLabelAttribute(): string {
		return self::TYPES[ $this->type ] ?? $this->type;
	}
}
