/**
 * Pemilih foto bertahap untuk <input type="file" multiple data-photo-picker>.
 *
 * Input file bawaan browser mengganti pilihan lama setiap kali memilih lagi, sehingga
 * member yang memilih foto satu per satu hanya mengunggah foto terakhir. Script ini
 * mengumpulkan semua pilihan, menampilkan preview dan tombol hapus. Dengan
 * data-photo-picker="cover", foto pertama ditandai "Utama" dan foto lain bisa dijadikan utama.
 */
(() => {
    const supportsDataTransfer = (() => {
        try { return new DataTransfer().files !== undefined; } catch { return false; }
    })();
    if (!supportsDataTransfer) return;

    document.querySelectorAll('input[type="file"][data-photo-picker]').forEach((input) => {
        const withCover = input.dataset.photoPicker === 'cover';
        const max = Number(input.dataset.max || 10);
        let files = [];

        const preview = document.createElement('div');
        preview.className = 'm-picker';
        input.insertAdjacentElement('afterend', preview);

        const key = (file) => `${file.name}|${file.size}|${file.lastModified}`;

        const sync = () => {
            const transfer = new DataTransfer();
            files.forEach((file) => transfer.items.add(file));
            input.files = transfer.files;
            render();
        };

        const render = () => {
            preview.querySelectorAll('img').forEach((img) => URL.revokeObjectURL(img.src));
            preview.innerHTML = '';
            files.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'm-picker__item' + (withCover && index === 0 ? ' is-cover' : '');

                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.alt = file.name;
                item.appendChild(img);

                if (withCover) {
                    const cover = document.createElement('button');
                    cover.type = 'button';
                    cover.className = 'm-picker__cover';
                    cover.textContent = index === 0 ? 'Utama' : 'Jadikan utama';
                    cover.disabled = index === 0;
                    cover.addEventListener('click', () => {
                        files.unshift(files.splice(index, 1)[0]);
                        sync();
                    });
                    item.appendChild(cover);
                }

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'm-picker__remove';
                remove.setAttribute('aria-label', `Hapus ${file.name}`);
                remove.innerHTML = '&times;';
                remove.addEventListener('click', () => {
                    files.splice(index, 1);
                    sync();
                });
                item.appendChild(remove);

                preview.appendChild(item);
            });

            if (files.length) {
                const info = document.createElement('p');
                info.className = 'm-picker__info';
                info.textContent = `${files.length} foto dipilih${files.length >= max ? ' (maksimal)' : ''}. Klik "Pilih file" lagi untuk menambah.`;
                preview.appendChild(info);
            }
        };

        input.addEventListener('change', () => {
            const known = new Set(files.map(key));
            const added = [...input.files].filter((file) => !known.has(key(file)));
            files = files.concat(added).slice(0, max);
            sync();
        });

        input.form?.addEventListener('reset', () => { files = []; sync(); });
    });
})();
