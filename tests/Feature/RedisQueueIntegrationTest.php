<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// Define a simple job within the test file to keep it self-contained
class DummyRedisIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public static $processed = false;

    public function handle()
    {
        self::$processed = true;
    }
}

test('real redis queue integration pushes job to redis and worker processes it', function () {
    // 1. Verify Redis is accessible before proceeding
    try {
        Redis::connection()->ping();
    } catch (\Exception $e) {
        $this->markTestSkipped('Redis server is not running or accessible: ' . $e->getMessage());
    }

    // 2. Set queue driver to redis dynamically for this test
    Config::set('queue.default', 'redis');

    // Reset the static tracking property
    DummyRedisIntegrationJob::$processed = false;

    // 3. Clear any existing jobs in the default Redis queue to ensure a clean run
    Redis::connection()->del('queues:default');

    // 4. Dispatch the job (it will be pushed to the real Redis queue)
    DummyRedisIntegrationJob::dispatch();

    // Verify that the job is indeed waiting in the Redis queue list
    $queueLength = Redis::connection()->llen('queues:default');
    expect($queueLength)->toBeGreaterThan(0);

    // 5. Run the Laravel queue worker once to process the job from Redis
    Artisan::call('queue:work', [
        'connection' => 'redis',
        '--once' => true,
    ]);

    // 6. Assert that the job's handle method was successfully executed
    expect(DummyRedisIntegrationJob::$processed)->toBeTrue();

    // Verify that the job was popped and queue is now empty
    $finalQueueLength = Redis::connection()->llen('queues:default');
    expect($finalQueueLength)->toBe(0);
});
