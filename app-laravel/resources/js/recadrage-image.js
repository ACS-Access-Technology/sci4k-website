const MAX_EDGE = 1600;

function ouvrirRecadrage(fichier) {
    return new Promise((resolve) => {
        const dialogue = document.createElement('dialog');
        dialogue.className = 'sci4k-crop-dialog';
        dialogue.innerHTML = `
            <form method="dialog" class="sci4k-crop-form">
                <h2>Recadrer l'image</h2>
                <p>Faites glisser l'image pour choisir la zone à conserver.</p>
                <div class="sci4k-crop-stage"><canvas></canvas></div>
                <div class="sci4k-crop-actions">
                    <button value="cancel" type="button" data-crop-cancel>Annuler</button>
                    <button value="confirm" type="button" data-crop-confirm>Utiliser cette image</button>
                </div>
            </form>`;
        document.body.append(dialogue);

        const canvas = dialogue.querySelector('canvas');
        const image = new Image();
        const context = canvas.getContext('2d');
        let offsetX = 0;
        let offsetY = 0;
        let zoom = 1;
        let dragging = false;
        let pointerX = 0;
        let pointerY = 0;

        const draw = () => {
            const size = Math.min(560, window.innerWidth - 48);
            const scale = Math.max(size / image.width, size / image.height) * zoom;
            canvas.width = size;
            canvas.height = size;
            const width = image.width * scale;
            const height = image.height * scale;
            const x = (size - width) / 2 + offsetX;
            const y = (size - height) / 2 + offsetY;
            context.clearRect(0, 0, size, size);
            context.drawImage(image, x, y, width, height);
        };

        const close = (result) => {
            dialogue.close();
            dialogue.remove();
            resolve(result);
        };

        image.onload = () => {
            draw();
            dialogue.showModal();
        };
        image.src = URL.createObjectURL(fichier);

        canvas.addEventListener('pointerdown', (event) => {
            dragging = true;
            pointerX = event.clientX;
            pointerY = event.clientY;
            canvas.setPointerCapture(event.pointerId);
        });
        canvas.addEventListener('pointermove', (event) => {
            if (!dragging) return;
            offsetX += event.clientX - pointerX;
            offsetY += event.clientY - pointerY;
            pointerX = event.clientX;
            pointerY = event.clientY;
            draw();
        });
        canvas.addEventListener('pointerup', () => { dragging = false; });
        canvas.addEventListener('wheel', (event) => {
            event.preventDefault();
            zoom = Math.max(1, Math.min(3, zoom + (event.deltaY > 0 ? -0.1 : 0.1)));
            draw();
        }, { passive: false });
        dialogue.querySelector('[data-crop-cancel]').addEventListener('click', () => close(null));
        dialogue.querySelector('[data-crop-confirm]').addEventListener('click', () => {
            const output = document.createElement('canvas');
            const size = Math.min(1200, Math.max(image.width, image.height));
            const scale = Math.max(size / image.width, size / image.height) * zoom;
            const width = image.width * scale;
            const height = image.height * scale;
            const x = (size - width) / 2 + offsetX * (size / canvas.width);
            const y = (size - height) / 2 + offsetY * (size / canvas.width);
            output.width = Math.min(MAX_EDGE, size);
            output.height = Math.min(MAX_EDGE, size);
            output.getContext('2d').drawImage(image, x, y, width, height);
            output.toBlob((blob) => close(new File([blob], fichier.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' })), 'image/jpeg', 0.9);
        });
        dialogue.addEventListener('cancel', (event) => {
            event.preventDefault();
            close(null);
        });
    });
}

async function recadrerFichiers(input, fichiers) {
    const resultats = [];
    for (const fichier of fichiers) {
        resultats.push(fichier.type === 'image/svg+xml' ? fichier : await ouvrirRecadrage(fichier));
    }

    const selectionnes = resultats.filter(Boolean);
    if (!selectionnes.length) return;
    const transfert = new DataTransfer();
    selectionnes.forEach((fichier) => transfert.items.add(fichier));
    input.files = transfert.files;
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

document.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || input.type !== 'file' || input.dataset.sci4kRecadrage === 'actif') return;
    const fichiers = [...input.files].filter((fichier) => fichier.type.startsWith('image/') && fichier.type !== 'image/svg+xml');
    if (!fichiers.length) return;
    event.stopImmediatePropagation();
    input.dataset.sci4kRecadrage = 'actif';
    recadrerFichiers(input, fichiers).finally(() => delete input.dataset.sci4kRecadrage);
}, true);