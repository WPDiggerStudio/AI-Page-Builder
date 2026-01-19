<?php

declare(strict_types=1);

namespace BraCalculator\App\Tasks;

use WPJarvis\Framework\WP\Scheduling\Task;

/**
 * SendAdminNotifications
 *
 * A scheduled task that sends daily digest notifications to admins.
 * Runs daily at 9 AM with summary of site activity.
 *
 * @package BraCalculator\App\Tasks
 */
class SendAdminNotifications extends Task
{
    /**
     * The task name.
     */
    protected string $name = 'send-admin-notifications';

    /**
     * The task description.
     */
    protected string $description = 'Send Admin Notifications Task';

    /**
     * Create a new task instance.
     */
    public function __construct()
    {
        // Run daily at 9:00 AM
        $this->dailyAt('09:00');

        // Only run in production
        $this->production();

        // Prevent overlapping
        $this->withoutOverlappingUsing(1800);
    }

    /**
     * Execute the scheduled task.
     *
     * @return mixed
     * @throws \Throwable
     */
    public function handle(): mixed
    {
        $this->log('Starting admin notification digest...');

        try {
            $result = $this->execute();
            $this->log("Notifications sent to {$result['recipients']} recipients.");
            return $result;
        } catch (\Throwable $e) {
            $this->log('Failed to send notifications: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Execute the main task logic.
     *
     * @return array{recipients: int, stats: array<string, mixed>}
     */
    protected function execute(): array
    {
        // Gather daily statistics
        $stats = $this->gatherDailyStats();

        // Get admin recipients
        $recipients = $this->getRecipients();

        if (empty($recipients)) {
            $this->log('No recipients configured for daily digest', 'warning');
            return ['recipients' => 0, 'stats' => $stats];
        }

        // Build email content
        $subject = $this->buildSubject($stats);
        $message = $this->buildMessage($stats);
        $headers = $this->getEmailHeaders();

        // Send to each recipient
        $sentCount = 0;
        foreach ($recipients as $email) {
            if (wp_mail($email, $subject, $message, $headers)) {
                $sentCount++;
            } else {
                $this->log("Failed to send to: {$email}", 'warning');
            }
        }

        return [
            'recipients' => $sentCount,
            'stats' => $stats,
        ];
    }

    /**
     * Gather daily statistics for the digest.
     *
     * @return array<string, mixed>
     */
    protected function gatherDailyStats(): array
    {
        global $wpdb;

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');

        // New posts published yesterday
        $newPosts = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_status = 'publish'
             AND post_type = 'post'
             AND DATE(post_date) = %s",
            $yesterday
        ));

        // New comments yesterday
        $newComments = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments}
             WHERE comment_approved = '1'
             AND DATE(comment_date) = %s",
            $yesterday
        ));

        // New users registered yesterday
        $newUsers = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->users}
             WHERE DATE(user_registered) = %s",
            $yesterday
        ));

        // Pending comments awaiting moderation
        $pendingComments = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '0'"
        );

        // Plugin-specific stats (customize for your plugin)
        $pluginStats = apply_filters('wpjarvis_daily_digest_stats', []);

        return [
            'date' => $yesterday,
            'new_posts' => $newPosts,
            'new_comments' => $newComments,
            'new_users' => $newUsers,
            'pending_comments' => $pendingComments,
            'plugin_stats' => $pluginStats,
        ];
    }

    /**
     * Get recipients for the daily digest.
     *
     * @return array<int, string>
     */
    protected function getRecipients(): array
    {
        // Get from plugin settings or default to admin email
        $recipients = get_option('bra_calculator_digest_recipients', '');

        if (!empty($recipients)) {
            return array_map('trim', explode(',', $recipients));
        }

        // Default: site admin
        return [get_option('admin_email')];
    }

    /**
     * Build email subject line.
     *
     * @param array<string, mixed> $stats Daily statistics.
     *
     * @return string
     */
    protected function buildSubject(array $stats): string
    {
        $siteName = get_bloginfo('name');
        $date = date('M j, Y', strtotime($stats['date']));

        return sprintf(
            __('[%s] Daily Digest for %s', 'bra-calculator'),
            $siteName,
            $date
        );
    }

    /**
     * Build email message body.
     *
     * @param array<string, mixed> $stats Daily statistics.
     *
     * @return string
     */
    protected function buildMessage(array $stats): string
    {
        $siteName = get_bloginfo('name');
        $siteUrl = home_url();
        $date = date('l, F j, Y', strtotime($stats['date']));

        $message = sprintf(
            __("Daily Digest for %s\n", 'bra-calculator') .
            __("Date: %s\n", 'bra-calculator') .
            __("Site: %s\n", 'bra-calculator') .
            "\n" .
            __("=== Activity Summary ===\n", 'bra-calculator') .
            "\n" .
            __("📝 New Posts: %d\n", 'bra-calculator') .
            __("💬 New Comments: %d\n", 'bra-calculator') .
            __("👤 New Users: %d\n", 'bra-calculator') .
            "\n" .
            __("=== Action Required ===\n", 'bra-calculator') .
            "\n" .
            __("⚠️ Pending Comments: %d\n", 'bra-calculator'),
            $siteName,
            $date,
            $siteUrl,
            $stats['new_posts'],
            $stats['new_comments'],
            $stats['new_users'],
            $stats['pending_comments']
        );

        // Add plugin-specific stats
        if (!empty($stats['plugin_stats'])) {
            $message .= "\n" . __("=== Plugin Stats ===\n", 'bra-calculator') . "\n";
            foreach ($stats['plugin_stats'] as $key => $value) {
                $message .= sprintf("%s: %s\n", ucfirst(str_replace('_', ' ', $key)), $value);
            }
        }

        // Add quick links
        $message .= "\n" . __("=== Quick Links ===\n", 'bra-calculator') . "\n";
        $message .= sprintf(__("Dashboard: %s\n", 'bra-calculator'), admin_url());
        $message .= sprintf(__("Comments: %s\n", 'bra-calculator'), admin_url('edit-comments.php'));
        $message .= sprintf(__("Posts: %s\n", 'bra-calculator'), admin_url('edit.php'));

        return $message;
    }

    /**
     * Get email headers.
     *
     * @return array<int, string>
     */
    protected function getEmailHeaders(): array
    {
        $fromName = get_bloginfo('name');
        $fromEmail = get_option('admin_email');

        return [
            "From: {$fromName} <{$fromEmail}>",
            'Content-Type: text/plain; charset=UTF-8',
        ];
    }

    /**
     * Log a message.
     *
     * @param string $message Message to log.
     * @param string $level Log level (info, error, warning).
     */
    protected function log(string $message, string $level = 'info'): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[%s] [%s] %s', strtoupper($level), $this->name, $message));
        }

        do_action('wpjarvis_task_log', $this->name, $message, $level);
    }

    /**
     * Handle task failure.
     *
     * @param \Throwable $e The exception that caused the failure.
     */
    public function handleFailure(\Throwable $e): void
    {
        parent::handleFailure($e);

        // Try to send a simple failure notification
        wp_mail(
            get_option('admin_email'),
            __('[URGENT] Daily Digest Failed', 'bra-calculator'),
            sprintf(
                __("The daily digest task failed with error:\n\n%s\n\nPlease check the error logs.", 'bra-calculator'),
                $e->getMessage()
            )
        );
    }
}
