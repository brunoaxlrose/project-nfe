<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('nfe:health', fn () => $this->info('NFe emitter is running.'))->purpose('Health check');
