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

/*
 * Reduction SANS recadrage, pour les champs qui refusent l'etape de cadrage.
 *
 * Un fond de section veut 1920 x 800 et un logo de partenaire ses proportions
 * d'origine : les passer par le recadreur, qui produit un carre, les abimerait.
 * Ils partaient donc TELS QUELS — c'est-a-dire, pour une photo venue d'un
 * telephone, quatre a huit megaoctets.
 *
 * PHP les jetait avant Laravel, sans validation ni journal : le backoffice
 * affichait « Envoi en cours… » un long moment, puis « le fichier n'a pas pu
 * etre envoye ». Relever la limite de PHP etait necessaire mais pas suffisant :
 * le fichier accepte serait ensuite servi tel quel a chaque visiteur.
 *
 * On borne donc le bord le plus long, en conservant les proportions. Une image
 * deja raisonnable n'est PAS touchee : la reencoder lui ferait perdre en
 * qualite sans rien gagner.
 */
const SEUIL_OCTETS = 1.5 * 1024 * 1024;

/* Une image opaque part en JPEG, bien plus leger. Une image qui porte de la
 * transparence reste en PNG : un logo de partenaire aplati sur du noir serait
 * inutilisable, et c'est precisement pour la preserver que ce champ saute le
 * recadrage. On regarde les pixels plutot que le type declare — un PNG est
 * opaque neuf fois sur dix. */
function porteDeLaTransparence(contexte, largeur, hauteur) {
    const pixels = contexte.getImageData(0, 0, largeur, hauteur).data;
    for (let i = 3; i < pixels.length; i += 4) {
        if (pixels[i] < 255) return true;
    }
    return false;
}

function chargerImage(fichier) {
    return new Promise((resolve, reject) => {
        const image = new Image();
        const adresse = URL.createObjectURL(fichier);
        image.onload = () => { URL.revokeObjectURL(adresse); resolve(image); };
        image.onerror = () => { URL.revokeObjectURL(adresse); reject(new Error('image illisible')); };
        image.src = adresse;
    });
}

async function reduireSiTropLourde(fichier) {
    let image;
    try {
        image = await chargerImage(fichier);
    } catch {
        // Illisible par le navigateur : on laisse passer, la validation du
        // serveur dira ce qui ne va pas. Refuser ici sans message serait
        // exactement le silence qu'on cherche a supprimer.
        //
        // Sans liaison sur le catch : l'exception ne nous apprend rien de plus
        // que « cette image ne se decode pas », et une variable qu'on ne lit
        // jamais laisse croire qu'on l'examine.
        return fichier;
    }

    const bordLePlusLong = Math.max(image.width, image.height);
    if (fichier.size <= SEUIL_OCTETS && bordLePlusLong <= MAX_EDGE) return fichier;

    const facteur = Math.min(1, MAX_EDGE / bordLePlusLong);
    const sortie = document.createElement('canvas');
    sortie.width = Math.round(image.width * facteur);
    sortie.height = Math.round(image.height * facteur);

    const contexte = sortie.getContext('2d');
    contexte.drawImage(image, 0, 0, sortie.width, sortie.height);

    const transparente = porteDeLaTransparence(contexte, sortie.width, sortie.height);
    const type = transparente ? 'image/png' : 'image/jpeg';
    const extension = transparente ? '.png' : '.jpg';

    const blob = await new Promise((resolve) => sortie.toBlob(resolve, type, 0.85));

    // Reduire a produit plus lourd : cela arrive sur un PNG deja optimise.
    // Garder l'original est alors le bon choix.
    if (!blob || blob.size >= fichier.size) return fichier;

    return new File([blob], fichier.name.replace(/\.[^.]+$/, '') + extension, { type });
}

async function recadrerFichiers(input, fichiers, transformer) {
    const resultats = [];
    for (const fichier of fichiers) {
        resultats.push(fichier.type === 'image/svg+xml' ? fichier : await transformer(fichier));
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
    // Certains champs refusent le recadrage : un logo de partenaire n'a pas de
    // proportions negociables, et le rendu JPEG lui ferait perdre sa
    // transparence. Le marqueur est pose par le gabarit, pas devine ici.
    //
    // Ils ne sont plus laisses de cote pour autant : ils passent par une simple
    // reduction, qui borne le bord le plus long sans toucher aux proportions.
    // Les ignorer tout a fait laissait partir des photos de plusieurs
    // megaoctets, que PHP jetait sans un mot.
    const transformer = input.dataset.sansRecadrage !== undefined ? reduireSiTropLourde : ouvrirRecadrage;
    const fichiers = [...input.files].filter((fichier) => fichier.type.startsWith('image/') && fichier.type !== 'image/svg+xml');
    if (!fichiers.length) return;
    event.stopImmediatePropagation();
    input.dataset.sci4kRecadrage = 'actif';
    recadrerFichiers(input, fichiers, transformer).finally(() => delete input.dataset.sci4kRecadrage);
}, true);