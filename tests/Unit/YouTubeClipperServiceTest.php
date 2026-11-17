<?php

namespace Tests\Unit;

use App\Models\VideoClip;
use App\Services\YouTubeClipperService;
use Tests\TestCase;

class YouTubeClipperServiceTest extends TestCase
{
    public function test_service_can_resolve_binaries_and_runtimes(): void
    {
        $service = new YouTubeClipperService();

        $ytDlpBin = $service->resolveYtDlpBinary();
        $this->assertNotEmpty($ytDlpBin);

        $ffmpegBin = $service->resolveFfmpegBinary();
        $this->assertNotEmpty($ffmpegBin);

        $jsRuntime = $service->resolveJsRuntimeSpec();
        $this->assertNotEmpty($jsRuntime);
    }
}
