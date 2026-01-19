<?php

declare(strict_types=1);

namespace BraCalculator\App\Mail;

use WPJarvis\Framework\WP\Mail\Mailable;

/**
 * TestMail
 *
 * A mailable class for sending Test Mail emails.
 *
 * @package BraCalculator\App\Mail
 */
class TestMail extends Mailable
{
    /**
     * Data to pass to the template.
     *
     * @var array<string, mixed>
     */
    public array $data;

    /**
     * Create a new mailable instance.
     *
     * @param array<string, mixed> $data Email data.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * Configure the email: recipients, subject, template, etc.
     */
    public function build(): static
    {
        return $this
            ->subject(__('Test Mail', 'bra-calculator'))
            ->from(get_option('admin_email'), get_bloginfo('name'))
            ->template('mail/test-mail', $this->buildTemplateData());
    }

    /**
     * Build the template data.
     *
     * @return array<string, mixed>
     */
    protected function buildTemplateData(): array
    {
        return array_merge([
            'siteName' => get_bloginfo('name'),
            'siteUrl' => home_url(),
            'year' => date('Y'),
            'supportEmail' => get_option('admin_email'),
        ], $this->data);
    }

    /**
     * Send to a specific user.
     *
     * @param \WP_User|int $user User object or ID.
     * @return static
     */
    public function toUser(\WP_User|int $user): static
    {
        if (is_int($user)) {
            $user = get_user_by('id', $user);
        }

        if ($user instanceof \WP_User) {
            $this->to($user->user_email, $user->display_name);
            $this->data['userName'] = $user->display_name;
            $this->data['userEmail'] = $user->user_email;
        }

        return $this;
    }

    /**
     * Send to the admin.
     *
     * @return static
     */
    public function toAdmin(): static
    {
        return $this->to(get_option('admin_email'), get_bloginfo('name'));
    }

    /**
     * Static factory method for fluent sending.
     *
     * Usage: TestMail::send(['key' => 'value'])->to('email@example.com');
     *
     * @param array<string, mixed> $data Email data.
     * @return static
     */
    public static function create(array $data = []): static
    {
        return new static($data);
    }

    /**
     * Send immediately with given data.
     *
     * @param string $to Recipient email.
     * @param array<string, mixed> $data Email data.
     * @return bool
     */
    public static function sendTo(string $to, array $data = []): bool
    {
        return static::create($data)->to($to)->send();
    }
}
