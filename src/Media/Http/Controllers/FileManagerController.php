<?php

namespace Amplify\System\Media\Http\Controllers;

use Amplify\System\Media\Events\BeforeInitialization;
use Amplify\System\Media\Events\Deleting;
use Amplify\System\Media\Events\DirectoryCreated;
use Amplify\System\Media\Events\DirectoryCreating;
use Amplify\System\Media\Events\DiskSelected;
use Amplify\System\Media\Events\Download;
use Amplify\System\Media\Events\FileCreated;
use Amplify\System\Media\Events\FileCreating;
use Amplify\System\Media\Events\FilesUploaded;
use Amplify\System\Media\Events\FilesUploading;
use Amplify\System\Media\Events\FilesUploadFailed;
use Amplify\System\Media\Events\FileUpdate;
use Amplify\System\Media\Events\Paste;
use Amplify\System\Media\Events\Rename;
use Amplify\System\Media\Events\Unzip as UnzipEvent;
use Amplify\System\Media\Events\Zip as ZipEvent;
use Amplify\System\Media\FileManager;
use Amplify\System\Media\Http\Requests\RequestValidator;
use Amplify\System\Media\Services\Zip;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileManagerController extends Controller
{
    /**
     * @var FileManager
     */
    public $fm;

    /**
     * FileManagerController constructor.
     *
     * @param  FileManager  $fm
     */
    public function __construct(FileManager $fm)
    {
        $this->fm = $fm;
    }

    /**
     * Initialize file manager
     *
     * @return JsonResponse
     */
    public function initialize(): JsonResponse
    {
        event(new BeforeInitialization);

        return response()->json(
            $this->fm->initialize()
        );
    }

    /**
     * Get files and directories for the selected path and disk
     *
     * @param  RequestValidator  $request
     *
     * @return JsonResponse
     * @throws FilesystemException
     */
    public function content(RequestValidator $request): JsonResponse
    {
        return response()->json(
            $this->fm->content(
                $request->input('disk'),
                $request->input('path')
            )
        );
    }

    /**
     * Directory tree
     *
     * @param  RequestValidator  $request
     *
     * @return JsonResponse
     * @throws FilesystemException
     */
    public function tree(RequestValidator $request): JsonResponse
    {
        return response()->json(
            $this->fm->tree(
                $request->input('disk'),
                $request->input('path')
            )
        );
    }

    /**
     * Check the selected disk
     *
     * @param  RequestValidator  $request
     *
     * @return JsonResponse
     */
    public function selectDisk(RequestValidator $request): JsonResponse
    {
        event(new DiskSelected($request->input('disk')));

        return response()->json([
            'result' => [
                'status' => 'success',
                'message' => 'diskSelected',
            ],
        ]);
    }

    /**
     * Upload files
     *
     * @param  RequestValidator  $request
     *
     * @return JsonResponse
     */
    public function upload(RequestValidator $request): JsonResponse
    {
        event(new FilesUploading($request));

        $uploadResponse = $this->fm->upload(
            $request->input('disk'),
            $request->input('path'),
            $request->file('files'),
            $request->input('overwrite')
        );
        $status = $uploadResponse['result']['status'];

        if ($status === "success")
            event(new FilesUploaded($request));
        else
            event(new FilesUploadFailed($request,$uploadResponse['result']['message']));


        return response()->json($uploadResponse);
    }

    /**
     * Delete files and folders
     *
     * @param  RequestValidator  $request
     *
     * @return JsonResponse
     */
    public function delete(RequestValidator $request): JsonResponse
    {
        event(new Deleting($request));

        $deleteResponse = $this->fm->delete(
            $request->input('disk'),
            $request->input('items')
        );

        return response()->json($deleteResponse);
    }

    /**
     * Copy / Cut files and folders
     *
     * @param  RequestValidator  $request
     *
     * @return JsonResponse
     */
    public function paste(RequestValidator $request): JsonResponse
    {
        event(new Paste($request));

        return response()->json(
            $this->fm->paste(
                $request->input('disk'),
                $request->input('path'),
                $request->input('clipboard')
            )
        );
    }

    /**
     * Rename
     *
     * @param  RequestValidator  $request
     *
     * @return JsonResponse
     */
    public function rename(RequestValidator $request): JsonResponse
    {
        event(new Rename($request));

        return response()->json(
            $this->fm->rename(
                $request->input('disk'),
                $request->input('newName'),
                $request->input('oldName')
            )
        );
    }

    /**
     * Download file
     *
     * @param  RequestValidator  $request
     *
     * @return StreamedResponse
     */
    public function download(RequestValidator $request): StreamedResponse
    {
        event(new Download($request));

        return $this->fm->download(
            $request->input('disk'),
            $request->input('path')
        );
    }

    /**
     * Create thumbnails
     *
     * @param  RequestValidator  $request
     *
     * @return Response|mixed
     * @throws BindingResolutionException
     */
    public function thumbnails(RequestValidator $request): mixed
    {
        return $this->fm->thumbnails(
            $request->input('disk'),
            $request->input('path')
        );
    }

    /**
     * Image preview
     *
     * @param  RequestValidator  $request
     *
     * @return mixed
     * @throws FileNotFoundException
     */
    public function preview(RequestValidator $request): mixed
    {
        return $this->fm->preview(
            $request->input('disk'),
            $request->input('path')
        );
    }

    /**
     * File url
     *
     * @param  RequestValidator  $request
     *
     * @return JsonResponse
     */
    public function url(RequestValidator $request): JsonResponse
    {
        return response()->json(
            $this->fm->url(
                $request->input('disk'),
                $request->input('path')
            )
        );
    }

    /**
     * Create new directory
     *
     * @param  RequestValidator  $request
     *
     * @return JsonResponse
     */
    public function createDirectory(RequestValidator $request): JsonResponse
    {
        event(new DirectoryCreating($request));

        $createDirectoryResponse = $this->fm->createDirectory(
            $request->input('disk'),
            $request->input('path'),
            $request->input('name')
        );

        if ($createDirectoryResponse['result']['status'] === 'success') {
            event(new DirectoryCreated($request));
        }

        return response()->json($createDirectoryResponse);
    }

    /**
     * Create new file
     *
     * @param  RequestValidator  $request
     *
     * @return JsonResponse
     */
    public function createFile(RequestValidator $request): JsonResponse
    {
        event(new FileCreating($request));

        $createFileResponse = $this->fm->createFile(
            $request->input('disk'),
            $request->input('path'),
            $request->input('name')
        );

        if ($createFileResponse['result']['status'] === 'success') {
            event(new FileCreated($request));
        }

        return response()->json($createFileResponse);
    }

    /**
     * Update file
     *
     * @param  RequestValidator  $request
     *
     * @return JsonResponse
     */
    public function updateFile(RequestValidator $request): JsonResponse
    {
        event(new FileUpdate($request));

        return response()->json(
            $this->fm->updateFile(
                $request->input('disk'),
                $request->input('path'),
                $request->file('file')
            )
        );
    }

    /**
     * Stream file
     *
     * @param  RequestValidator  $request
     *
     * @return mixed
     */
    public function streamFile(RequestValidator $request): mixed
    {
        return $this->fm->streamFile(
            $request->input('disk'),
            $request->input('path')
        );
    }

    /**
     * Create zip archive
     *
     * @param  RequestValidator  $request
     * @param  Zip  $zip
     *
     * @return array
     */
    public function zip(RequestValidator $request, Zip $zip)
    {
        event(new ZipEvent($request));

        return $zip->create();
    }

    /**
     * Extract zip archive
     *
     * @param  RequestValidator  $request
     * @param  Zip  $zip
     *
     * @return array
     */
    public function unzip(RequestValidator $request, Zip $zip)
    {
        event(new UnzipEvent($request));

        return $zip->extract();
    }

    /**
     * Integration with ckeditor 4
     *
     * @return Factory|View
     */
    public function ckeditor(): Factory|View
    {
        return view('file-manager::ckeditor');
    }

    /**
     * Integration with TinyMCE v4
     *
     * @return Factory|View
     */
    public function tinymce(): Factory|View
    {
        return view('file-manager::tinymce');
    }

    /**
     * Integration with TinyMCE v5
     *
     * @return Factory|View
     */
    public function tinymce5(): Factory|View
    {
        return view('file-manager::tinymce5');
    }

    /**
     * Integration with SummerNote
     *
     * @return Factory|View
     */
    public function summernote(): Factory|View
    {
        return view('file-manager::summernote');
    }

    /**
     * Simple integration with input field
     *
     * @return Factory|View
     */
    public function fmButton(): Factory|View
    {
        return view('file-manager::fmButton');
    }
}