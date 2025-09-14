<?php

namespace Amplify\System\Helpers;

use Amplify\System\Utility\Models\DataTransformationError;
use Amplify\System\Utility\Models\IcecatTransformationError;
use Amplify\System\Utility\Models\ImportError;
use Illuminate\Support\Facades\DB;

class DBHelper
{
    /**
     * @var mixed
     */
    private static $job;

    /**
     * @var mixed
     */
    private static $importData;

    /**
     * @var mixed
     */
    private static $importDataKeys;

    public static function updateJobPayload(array $uuids, array $updateImportData, $id = null, string $table = 'failed_jobs')
    {
        collect($uuids)->each(function ($uuid, $index) use ($table, $updateImportData, $id) {
            // Find in failed_jobs table
            $instance = DB::table($table)->where('uuid', $uuid);
            $failed = $instance->first();

            if (! $failed) {
                abort(404);
            }

            $payload = json_decode($failed->payload);
            $command = $payload->data->command;
            self::$job = unserialize($command);

            // Find in import_errors table
            $importError = ImportError::query()->where(['uuid' => $uuid, 'import_job_id' => $id])->first();
            self::$importData = json_decode($importError->import_data);
            self::$importDataKeys = collect(self::$importData)->keys();

            collect($updateImportData[$index])->each(function ($data, $ind) {
                self::$importData->{self::$importDataKeys[$ind]} = $data;
                self::$job->aCsv[$ind] = $data;
            });

            // Save import_errors table
            $importError->import_data = json_encode(self::$importData);
            $importError->save();

            self::$job = serialize(self::$job);
            $payload->data->command = self::$job;
            $failed->payload = json_encode($payload);

            // Save failed_jobs table
            $instance->update(['payload' => $failed->payload]);
        });
    }

    public static function updateJobPayloadForDataTransformation(array $uuids, array $updateImportData, $id = null, string $table = 'failed_jobs')
    {
        collect($uuids)->each(function ($uuid, $index) use ($table, $updateImportData, $id) {
            // Find in failed_jobs table
            $instance = DB::table($table)->where('uuid', $uuid);
            $failed = $instance->first();

            if (! $failed) {
                abort(404);
            }

            $payload = json_decode($failed->payload);
            $command = $payload->data->command;
            self::$job = unserialize($command);

            // Find in import_errors table
            $dataTransformationError = DataTransformationError::query()->where(['uuid' => $uuid, 'data_transformation_id' => $id])->first();
            self::$importData = json_decode($dataTransformationError->data_transformation);
            self::$importDataKeys = collect(self::$importData)->keys();

            collect($updateImportData[$index])->each(function ($data, $ind) {
                self::$importData->{self::$importDataKeys[$ind]} = $data;
                self::$job->aCsv[$ind] = $data;
            });

            // Save import_errors table
            $dataTransformationError->data_transformation = json_encode(self::$importData);
            $dataTransformationError->save();

            self::$job = serialize(self::$job);
            $payload->data->command = self::$job;
            $failed->payload = json_encode($payload);

            // Save failed_jobs table
            $instance->update(['payload' => $failed->payload]);
        });
    }

    public static function updateJobPayloadForIcecatTransformation(array $uuids, array $updateImportData, $id = null, string $table = 'failed_jobs')
    {
        collect($uuids)->each(function ($uuid, $index) use ($table, $updateImportData, $id) {
            // Find in failed_jobs table
            $instance = DB::table($table)->where('uuid', $uuid);
            $failed = $instance->first();

            if (! $failed) {
                abort(404);
            }

            $payload = json_decode($failed->payload);
            $command = $payload->data->command;
            self::$job = unserialize($command);

            // Find in import_errors table
            $icecatTransformationError = IcecatTransformationError::query()->where(['uuid' => $uuid, 'icecat_transformation_id' => $id])->first();
            self::$importData = json_decode($icecatTransformationError->icecat_transformation);
            self::$importDataKeys = collect(self::$importData)->keys();

            collect($updateImportData[$index])->each(function ($data, $ind) {
                self::$importData->{self::$importDataKeys[$ind]} = $data;
                self::$job->aCsv[$ind] = $data;
            });

            // Save import_errors table
            $icecatTransformationError->icecat_transformation = json_encode(self::$importData);
            $icecatTransformationError->save();

            self::$job = serialize(self::$job);
            $payload->data->command = self::$job;
            $failed->payload = json_encode($payload);

            // Save failed_jobs table
            $instance->update(['payload' => $failed->payload]);
        });
    }
}
