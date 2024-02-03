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
            <div class="log">
                <h2>Log</h2>
                <div id="log" style="height: 500px; overflow-y: scroll;"></div>
            </div>
        </div>
        <script>
            /*
            * Default chunk size is 1MB
            * Retry up to 120 times (2 minutes) with 1 second delay between each retry
            * */
            function uploadFile(file, url, chunkSize = 1 * 1024 * 1024, uploadedChunks = []) {
                let start = 0;
                const totalSize = file.size;
                let chunkIndex = 0;

                const uploadChunk = (retryCount = 0) => {
                    if (start >= totalSize) {
                        console.log("Upload completed");
                        document.getElementById('log').innerHTML += "Upload completed<br>";
                        return;
                    }

                    // Пропускаємо чанк, якщо він уже завантажений
                    if (uploadedChunks.includes(chunkIndex)) {
                        console.log(`Skipping chunk ${chunkIndex + 1} as it is already uploaded.`);
                        document.getElementById('log').innerHTML += `Skipping chunk ${chunkIndex + 1} as it is already uploaded.<br>`;
                        start += chunkSize;
                        chunkIndex++;
                        uploadChunk();
                        return;
                    }

                    const end = Math.min(start + chunkSize, totalSize);
                    const chunk = file.slice(start, end);
                    const formData = new FormData();
                    formData.append("fileChunk", chunk);
                    formData.append("fileName", file.name);
                    formData.append("chunkIndex", chunkIndex);
                    formData.append("totalChunks", Math.ceil(totalSize / chunkSize));
                    formData.append("_token", "{{ csrf_token() }}");


                    fetch(url, {
                        method: "POST",
                        body: formData,
                    }).then(response => {
                        if (response.ok) {
                            console.log(`Chunk ${chunkIndex + 1}/${Math.ceil(totalSize / chunkSize)} uploaded successfully.`);
                            document.getElementById('log').innerHTML += `Chunk ${chunkIndex + 1}/${Math.ceil(totalSize / chunkSize)} uploaded successfully.<br>`;
                            start = end;
                            chunkIndex++;
                            uploadChunk(); // Upload next chunk
                        } else {
                            throw new Error('Upload failed');
                        }
                    }).catch(error => {
                        console.error("Error uploading chunk:", error);
                        if (retryCount < 3) { // Retry up to 3 times
                            setTimeout(() => {
                                console.log(`Retrying chunk ${chunkIndex + 1}...`);
                                document.getElementById('log').innerHTML += `Retrying chunk ${chunkIndex + 1}...<br>`;
                                uploadChunk(retryCount + 1);
                            }, 1000);
                        } else {
                            console.error("Failed to upload chunk after multiple attempts.");
                        }
                    });
                };

                uploadChunk();
            }

            function checkUploadedChunks(file, url) {
                const formData = new FormData();
                formData.append("fileName", file.name);
                formData.append("totalChunks", Math.ceil(file.size / (1 * 1024 * 1024)));
                formData.append("_token", "{{ csrf_token() }}");


                return fetch(url + '/check', {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => response.json())
                    .then(data => data.uploadedChunks);
            }

            document.querySelector('input[type="file"]').addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    checkUploadedChunks(file, 'http://127.0.0.1:8000/upload').then(uploadedChunks => {
                        uploadFile(file, 'http://127.0.0.1:8000/upload', 1 * 1024 * 1024, uploadedChunks);
                    });
                }
            });


        </script>
    </body>
</html>
