<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function upload(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'fileName' => 'required|string',
            'chunkIndex' => 'required|integer',
            'totalChunks' => 'required|integer',
            'fileChunk' => 'required|file',
        ]);

        $fileName = $request->input('fileName');
        $chunkIndex = $request->input('chunkIndex');
        $totalChunks = $request->input('totalChunks');
        $chunk = $request->file('fileChunk');

        $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'chunks' . DIRECTORY_SEPARATOR . $fileName);
        $finalDir = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads');

        if (!file_exists($finalDir)) {
            mkdir($finalDir, 0777, true);
        }

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $tempFilePath = $tempDir . DIRECTORY_SEPARATOR . $fileName . '.part' . $chunkIndex;

        $chunk->move($tempDir, $fileName . '.part' . $chunkIndex);

        $allChunksUploaded = true;
        for ($i = 0; $i < $totalChunks; $i++) {
            if (!file_exists($tempDir . DIRECTORY_SEPARATOR . $fileName . '.part' . $i)) {
                $allChunksUploaded = false;
                break;
            }
        }

        if ($allChunksUploaded) {
            $finalFilePath = $finalDir . DIRECTORY_SEPARATOR . $fileName;

            if (!$fileHandle = fopen($finalFilePath, 'wb')) {
                Log::error("Failed to open file: {$finalFilePath}");
                return response()->json(['error' => 'Failed to open file for writing.'], 500);
            }

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkFilePath = $tempDir . DIRECTORY_SEPARATOR . $fileName . '.part' . $i;
                $fileData = file_get_contents($chunkFilePath);
                fwrite($fileHandle, $fileData);
                unlink($chunkFilePath);
            }

            fclose($fileHandle);
            rmdir($tempDir);

            return response()->json(['message' => 'File reassembled successfully.'], 200);
        }

        return response()->json(['message' => 'Chunk received'], 200);
    }

    public function checkUploadedChunks(Request $request): \Illuminate\Http\JsonResponse
    {
        $fileName = $request->input('fileName');
        $totalChunks = $request->input('totalChunks');
        $tempDir = storage_path('app/public/chunks/' . $fileName);

        $uploadedChunks = [];
        for ($i = 0; $i < $totalChunks; $i++) {
            if (file_exists($tempDir . '/' . $fileName . '.part' . $i)) {
                $uploadedChunks[] = $i;
            }
        }

        return response()->json(['uploadedChunks' => $uploadedChunks]);
    }
}
