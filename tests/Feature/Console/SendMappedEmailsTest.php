<?php

namespace Tests\Feature\Console;

use App\Console\Commands\SendMappedEmails;
use App\Models\EmailTemplate;
use App\Models\Shooter;
use App\Models\ShooterTargetMapping;
use App\Models\Target;
use App\Services\EmailRenderService;
use App\Services\GmailSendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SendMappedEmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_shooter_token_failure_does_not_stop_others()
    {
        // Arrange: Create 2 shooters
        $shooter1 = Shooter::factory()->create(['email' => 'shooter1@example.com']);
        $shooter2 = Shooter::factory()->create(['email' => 'shooter2@example.com']);

        // Create targets and template
        $target1 = Target::factory()->create(['email' => 'target1@example.com']);
        $target2 = Target::factory()->create(['email' => 'target2@example.com']);
        $template = EmailTemplate::factory()->create();

        // Arrange: Create mappings
        $mapping1 = ShooterTargetMapping::factory()->create([
            'shooter_id' => $shooter1->id,
            'target_id' => $target1->id,
            'email_template_id' => $template->id,
            'status' => 'assigned',
            'assigned_date' => now()->toDateString(),
        ]);

        $mapping2 = ShooterTargetMapping::factory()->create([
            'shooter_id' => $shooter2->id,
            'target_id' => $target2->id,
            'email_template_id' => $template->id,
            'status' => 'assigned',
            'assigned_date' => now()->toDateString(),
        ]);

        // Mock GmailSendService
        $this->mock(GmailSendService::class, function (MockInterface $mock) use ($shooter1, $shooter2) {
            // First shooter fails with token error
            $mock->shouldReceive('send')
                ->withArgs(function ($shooter) use ($shooter1) {
                    return $shooter->id === $shooter1->id;
                })
                ->once()
                ->andThrow(new \Exception('Error refreshing access token'));

            // Second shooter succeeds
            $mock->shouldReceive('send')
                ->withArgs(function ($shooter) use ($shooter2) {
                    return $shooter->id === $shooter2->id;
                })
                ->once()
                ->andReturn(true);
        });

        // Mock EmailRenderService (simple pass-through)
        $this->mock(EmailRenderService::class, function (MockInterface $mock) {
            $mock->shouldReceive('render')->andReturn([
                'to' => 'test@example.com',
                'subject' => 'Test',
                'body' => 'Body',
                'attachment' => null,
            ]);
        });

        // Act: Run command
        $this->artisan('emails:send-mapped')
            ->assertExitCode(0);

        // Assert: Mapping 1 failed
        $this->assertDatabaseHas('shooter_target_mappings', [
            'id' => $mapping1->id,
            'status' => 'failed',
        ]);

        // Assert: Mapping 2 sent
        $this->assertDatabaseHas('shooter_target_mappings', [
            'id' => $mapping2->id,
            'status' => 'sent',
        ]);
    }
}
