<?php

namespace Tests\Unit;

use App\Support\RichTextSanitizer;
use PHPUnit\Framework\TestCase;

class RichTextSanitizerTest extends TestCase
{
    public function test_sanitize_html_removes_nbsp_only_content(): void
    {
        $this->assertNull(RichTextSanitizer::sanitizeHtml('<p>&nbsp;</p>'));
        $this->assertNull(RichTextSanitizer::sanitizeHtml('<p> &nbsp; </p>'));
        $this->assertNull(RichTextSanitizer::sanitizeHtml('<p><br></p>'));
        $this->assertNull(RichTextSanitizer::sanitizeHtml('<p><br/></p>'));
    }

    public function test_sanitize_html_preserves_valid_html_tags(): void
    {
        $input = '<p>Menyelesaikan <strong>laporan</strong> <em>harian</em>.</p><ul><li>Satu</li><li>Dua</li></ul><blockquote>Kutipan</blockquote>';
        $output = RichTextSanitizer::sanitizeHtml($input);

        $this->assertStringContainsString('<p>', $output);
        $this->assertStringContainsString('<strong>laporan</strong>', $output);
        $this->assertStringContainsString('<em>harian</em>', $output);
        $this->assertStringContainsString('<ul>', $output);
        $this->assertStringContainsString('<li>', $output);
        $this->assertStringContainsString('<blockquote>', $output);
    }

    public function test_sanitize_html_replaces_nbsp_but_keeps_html(): void
    {
        $output = RichTextSanitizer::sanitizeHtml('<p>Rencana&nbsp;kerja&nbsp;hari&nbsp;ini</p>');

        $this->assertSame('<p>Rencana kerja hari ini</p>', $output);
    }

    public function test_sanitize_html_is_idempotent(): void
    {
        $input = '<p>Hello <strong>world</strong></p>';
        $once = RichTextSanitizer::sanitizeHtml($input);
        $twice = RichTextSanitizer::sanitizeHtml($once);

        $this->assertSame($once, $twice);
    }

    public function test_to_plain_text_decodes_nbsp_entities(): void
    {
        $this->assertSame(
            'Rencana kerja hari ini',
            RichTextSanitizer::toPlainText('<p>Rencana&nbsp;kerja&nbsp;hari&nbsp;ini</p>'),
        );
    }

    public function test_to_plain_text_returns_null_for_empty_html(): void
    {
        $this->assertNull(RichTextSanitizer::toPlainText('<p>&nbsp;</p>'));
        $this->assertNull(RichTextSanitizer::toPlainText(null));
    }

    public function test_sanitize_plain_text_normalizes_leave_note(): void
    {
        $this->assertSame(
            'Izin keperluan keluarga',
            RichTextSanitizer::sanitizePlainText('Izin&nbsp;keperluan&nbsp;keluarga'),
        );
    }
}
