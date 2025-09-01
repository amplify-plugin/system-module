<?php

namespace Amplify\System\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCoilPdfProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $information;

    protected $name;

    /**
     * Create a new job instance.
     */
    public function __construct($data, $name)
    {
        $this->information = $data;
        $this->name = $name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pdf = Pdf::loadView('custom-item::evaporator_coil_pdf', ['info' => $this->information])->save(storage_path($this->name));
    }
}
