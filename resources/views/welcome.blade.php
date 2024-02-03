<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />


    </head>
    <body>
        <div class="flex-center position-ref full-height">
            <div class="content">
                <div class="title m-b-md">
                    <input type="file" id="fileInput">


                </div>
            </div>
        </div>
        <script>

            /*
            * Default chunk size is 1MB
            * Retry up to 3 times
            * */
            function uploadFile(file, url, chunkSize = 1 * 1024 * 1024) { // Default chunk size is 1MB
                let start = 0;
                const totalSize = file.size;
                let chunkIndex = 0;

                const uploadChunk = (retryCount = 0) => {
                    if (start >= totalSize) {
                        console.log("Upload completed");
                        return;
                    }

                    const end = Math.min(start + chunkSize, totalSize);
                    const chunk = file.slice(start, end);
                    const formData = new FormData();
                    formData.append("fileChunk", chunk);
                    formData.append("fileName", file.name);
                    formData.append("chunkIndex", chunkIndex);
                    formData.append("totalChunks", Math.ceil(totalSize / chunkSize));
                    formData.append("_token", "{{ csrf_token() }}"); // Laravel CSRF token

                    fetch(url, {
                        method: "POST",
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }).then(response => {
                        if (response.ok) {
                            console.log(`Chunk ${chunkIndex + 1}/${Math.ceil(totalSize / chunkSize)} uploaded successfully.`);
                            start = end;
                            chunkIndex++;
                            uploadChunk(); // Upload next chunk
                        } else {
                            throw new Error('Upload failed');
                        }
                    }).catch(error => {
                        console.error("Error uploading chunk:", error);
                        if (retryCount < 3) { // Retry up to 3 times
                            console.log(`Retrying chunk ${chunkIndex + 1}...`);
                            uploadChunk(retryCount + 1);
                        } else {
                            console.error("Failed to upload chunk after multiple attempts.");
                        }
                    });
                };

                uploadChunk();
            }

            document.querySelector('input[type="file"]').addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    uploadFile(file, '/upload');
                }
            });
        </script>
    </body>
</html>
