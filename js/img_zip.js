function img_zip(file, quality = 0.95) {

    return new Promise((resolve, reject) => {

        const img = new Image();

        img.onload = function() {

            const canvas =
                document.createElement('canvas');

            const ctx =
                canvas.getContext('2d');

            const maxWidth = 1920;
            const maxHeight = 2048;

            let width = img.width;
            let height = img.height;

            // 等比例缩放
            if (
                width > maxWidth ||
                height > maxHeight
            ) {

                const ratio = Math.min(
                    maxWidth / width,
                    maxHeight / height
                );

                width *= ratio;
                height *= ratio;
            }

            canvas.width = width;
            canvas.height = height;

            ctx.drawImage(
                img,
                0,
                0,
                width,
                height
            );

            canvas.toBlob(

                blob => {

                    if (!blob) {
                        reject(
                            new Error("图片压缩失败")
                        );
                        return;
                    }

                    resolve(blob);

                    URL.revokeObjectURL(img.src);

                },

                'image/jpeg',

                quality
            );
        };

        img.onerror = reject;

        img.src = URL.createObjectURL(file);
    });
}