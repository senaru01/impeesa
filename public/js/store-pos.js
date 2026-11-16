/**
 * Toko: pratinjau 3D gantungan kunci, estimasi harga, warna per huruf, kembalian,
 * warna swatch filament, dan pilih-banyak item. Harga final tetap dihitung di server.
 */
(() => {
    const rupiah = (value) => 'Rp ' + Math.round(value).toLocaleString('id-ID');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const palette = {
        merah: '#dc2626', maroon: '#7f1d1d', biru: '#2563eb', navy: '#1e3a8a', 'biru muda': '#60a5fa', hijau: '#16a34a',
        tosca: '#14b8a6', mint: '#86efac', kuning: '#facc15', oranye: '#f97316', orange: '#f97316', ungu: '#7c3aed',
        lilac: '#c4b5fd', pink: '#ec4899', hitam: '#1f2937', putih: '#f8fafc', abu: '#9ca3af', silver: '#cbd5e1',
        emas: '#d4a017', gold: '#d4a017', coklat: '#92400e', cream: '#f5e6c8', krem: '#f5e6c8', transparan: '#dbeafe', glow: '#bef264',
        // Nama warna filament (Inggris) yang umum di merek seperti Vilum, Sunlu, eSun.
        red: '#dc2626', blue: '#2563eb', green: '#16a34a', yellow: '#facc15', purple: '#7c3aed', violet: '#7c3aed',
        white: '#f8fafc', black: '#1f2937', grey: '#9ca3af', gray: '#9ca3af', brown: '#92400e', beige: '#e7d7b8',
        teal: '#14b8a6', cyan: '#22d3ee', lime: '#a3e635', peach: '#fdba8c', lavender: '#c4b5fd', sky: '#7dd3fc',
        'ice blue': '#a5d8f2', 'sky blue': '#7dd3fc', 'baby blue': '#a7c7e7', 'milk green': '#b9e4c9', 'mint green': '#98e2c0',
        'pale pink': '#f6b8d0', 'pale purple': '#cbb8ea', 'pastel pink': '#f6b8d0', 'pastel purple': '#cbb8ea',
        'pastel blue': '#a7c7e7', 'pastel green': '#b9e4c9', 'pastel yellow': '#fbe7a1', 'light blue': '#93c5fd',
        'light green': '#86efac', 'dark blue': '#1e3a8a', 'dark green': '#166534', 'army green': '#4b5320', 'rose gold': '#d8a39d',
    };

    const swatch = (name) => {
        const key = (name || '').toLowerCase();
        const match = Object.keys(palette).sort((a, b) => b.length - a.length).find((color) => key.includes(color));

        return match ? palette[match] : '#cbd5e1';
    };

    const inkFor = (hex) => {
        const [r, g, b] = [1, 3, 5].map((start) => parseInt(hex.slice(start, start + 2), 16));

        return (r * 299 + g * 587 + b * 114) / 1000 > 150 ? '#0f172a' : '#ffffff';
    };

    /** Kunci varian keycap "Putih|Hitam" → warna cap dan warna huruf. */
    const splitVariant = (key) => {
        const [cap, letter] = (key || '').split('|');

        return { cap: cap || '', letter: letter || '' };
    };
    const variantLabel = (key) => {
        const { cap, letter } = splitVariant(key);

        return letter ? `${cap}, huruf ${letter}` : cap;
    };

    document.querySelectorAll('[data-swatch]').forEach((element) => {
        element.style.background = swatch(element.dataset.swatch);
    });

    /* ---------------- Model 3D (CSS 3D transforms) ---------------- */
    /*
     * Bentuk mengikuti produk jadi: base berupa wadah tebal berlekuk per huruf, keycap kotak
     * tumpul duduk di dalam lubangnya, dan rantai bola di ujung kiri. Sisi yang membulat dibuat
     * dari irisan tipis yang ditumpuk (seperti layer cetak 3D) dengan warna makin gelap ke bawah.
     */
    const SIZE = {
        cap: 46, wall: 5, pitch: 55, radius: 12, capRadius: 9,
        baseHeight: 60, capRise: 11, capSink: 7, capTaper: 4, layer: 2,
    };

    const shade = (hex, factor) => {
        const channels = [1, 3, 5].map((start) => Math.round(parseInt(hex.slice(start, start + 2), 16) * factor));

        return `rgb(${channels.join(',')})`;
    };

    const slice = (left, top, width, height, z, radius, background, className = 'k3d-slice') => {
        const element = document.createElement('div');
        element.className = className;
        Object.assign(element.style, {
            left: `${left}px`, top: `${top}px`, width: `${width}px`, height: `${height}px`,
            borderRadius: `${radius}px`, background, transform: `translateZ(${z}px)`,
        });

        return element;
    };

    /**
     * Tumpukan irisan untuk satu bentuk kotak tumpul, dari z bawah sampai z atas.
     */
    const solid = (parent, left, top, width, height, radius, color, fromZ, toZ, darkest = 0.55, lightest = 0.84, taper = 0) => {
        const steps = Math.max(1, Math.round((toZ - fromZ) / SIZE.layer));

        for (let step = 0; step < steps; step++) {
            const progress = step / steps;
            const inset = taper * progress;
            parent.append(slice(left + inset, top + inset, width - inset * 2, height - inset * 2, fromZ + progress * (toZ - fromZ), radius, shade(color, darkest + (lightest - darkest) * progress)));
        }
    };

    const ballChain = (model, depth, chainHex = null) => {
        const centerX = -66;
        const centerY = depth / 2 + 8;
        const radiusX = 50;
        const radiusY = 30;
        const beads = 42;
        const z = SIZE.baseHeight * 0.5;

        for (let index = 0; index < beads; index++) {
            const angle = (index / beads) * Math.PI * 2;
            const x = centerX + Math.cos(angle) * radiusX;
            const y = centerY + Math.sin(angle) * radiusY;

            if (x > -18) {
                continue;
            }

            const bead = document.createElement('span');
            bead.className = 'k3d-bead';
            if (chainHex) {
                bead.style.background = `radial-gradient(circle at 35% 30%, #ffffff 0%, ${chainHex} 45%, ${shade(chainHex, 0.55)} 100%)`;
            }
            bead.style.transform = `translate3d(${x - 3.5}px, ${y - 3.5}px, ${z}px)`;
            model.append(bead);
        }

        const clasp = document.createElement('span');
        clasp.className = 'k3d-clasp';
        if (chainHex) {
            clasp.style.background = `linear-gradient(180deg, ${chainHex}, ${shade(chainHex, 0.6)})`;
        }
        clasp.style.transform = `translate3d(${centerX - radiusX - 7}px, ${centerY - 4}px, ${z}px)`;
        model.append(clasp);

        // Cincin gantungan: silinder kecil yang menonjol di ujung base, lubangnya menghadap samping.
        for (let ring = -3; ring <= 3; ring++) {
            const eyelet = document.createElement('span');
            eyelet.className = 'k3d-eyelet';
            eyelet.style.transform = `translate3d(-16px, ${depth / 2 - 9 + ring}px, ${z - 9}px) rotateX(90deg)`;
            model.append(eyelet);
        }
    };

    const buildModel = (chars, capColors, baseColor, missing, chainColor = '') => {
        const pod = SIZE.cap + SIZE.wall * 2;
        const width = pod + (chars.length - 1) * SIZE.pitch;
        const depth = pod;
        const baseHex = swatch(baseColor);
        const model = document.createElement('div');
        model.className = 'k3d__model';
        model.style.width = `${width}px`;
        model.style.height = `${depth}px`;
        model.style.marginLeft = '126px';
        model.style.setProperty('--eyelet', shade(baseHex, 0.85));

        const shadow = document.createElement('div');
        shadow.className = 'k3d__shadow';
        model.append(shadow);

        ballChain(model, depth, chainColor ? swatch(chainColor) : null);

        // Dinding base: satu "pod" kotak tumpul per huruf yang saling menyambung (ada takik di antaranya).
        chars.forEach((char, index) => {
            solid(model, index * SIZE.pitch, 0, pod, pod, SIZE.radius, baseHex, 0, SIZE.baseHeight, 0.5, 0.86);
        });

        // Bibir atas base, lalu lubang tempat keycap duduk.
        chars.forEach((char, index) => {
            model.append(slice(index * SIZE.pitch, 0, pod, pod, SIZE.baseHeight, SIZE.radius, shade(baseHex, 0.95), 'k3d-slice k3d-rim'));
        });
        chars.forEach((char, index) => {
            model.append(slice(index * SIZE.pitch + SIZE.wall - 2, SIZE.wall - 2, SIZE.cap + 4, SIZE.cap + 4, SIZE.baseHeight + 0.3, SIZE.capRadius + 2, shade(baseHex, 0.28), 'k3d-slice k3d-pocket'));
        });

        chars.forEach((char, index) => {
            const variant = splitVariant(capColors[index]);
            const colorHex = swatch(variant.cap);
            const cap = document.createElement('div');
            cap.className = 'k3d-cap' + (missing[index] ? ' is-missing' : '');
            cap.dataset.index = index;
            cap.title = `${char} · ${variantLabel(capColors[index])}`;
            const left = index * SIZE.pitch + SIZE.wall;
            const bottom = SIZE.baseHeight - SIZE.capSink;
            const top = SIZE.baseHeight + SIZE.capRise;

            solid(cap, left, SIZE.wall, SIZE.cap, SIZE.cap, SIZE.capRadius, colorHex, bottom, top, 0.62, 0.9, SIZE.capTaper);

            const inset = SIZE.capTaper;
            const face = slice(left + inset, SIZE.wall + inset, SIZE.cap - inset * 2, SIZE.cap - inset * 2, top, SIZE.capRadius - 2, colorHex, 'k3d-slice k3d-cap__top');
            const legend = document.createElement('b');
            legend.textContent = char;
            legend.style.color = variant.letter ? swatch(variant.letter) : (inkFor(colorHex) === '#ffffff' ? '#f8fafc' : '#111827');
            face.append(legend);
            cap.append(face);
            model.append(cap);
        });

        return { model, width: width + 130 };
    };

    const createScene = (container, onCapPick = null) => {
        const stage = document.createElement('div');
        stage.className = 'k3d__stage';
        const hint = document.createElement('span');
        hint.className = 'k3d__hint';
        hint.textContent = onCapPick ? 'Geser untuk memutar · klik keycap atau rantai untuk ganti warna' : 'Geser untuk memutar · klik keycap untuk "klik"';
        container.replaceChildren(stage, hint);

        const view = { rx: 46, rz: -12, scale: 1, dragging: false, lastX: 0, lastY: 0, idle: 0, moved: 0 };
        const apply = (sway = 0) => {
            stage.style.transform = `rotateX(${view.rx}deg) rotateZ(${view.rz + sway}deg) scale(${view.scale})`;
        };

        container.addEventListener('pointerdown', (event) => {
            if (event.target.closest('.k3d-picker')) {
                return;
            }
            view.dragging = true;
            view.moved = 0;
            view.pressedCap = event.target.closest('.k3d-cap');
            view.pressedChain = Boolean(event.target.closest('.k3d-bead, .k3d-clasp, .k3d-eyelet'));
            view.lastX = event.clientX;
            view.lastY = event.clientY;
            container.setPointerCapture(event.pointerId);
        });
        container.addEventListener('pointermove', (event) => {
            if (!view.dragging) {
                return;
            }
            view.moved += Math.abs(event.clientX - view.lastX) + Math.abs(event.clientY - view.lastY);
            view.rz += (event.clientX - view.lastX) * 0.4;
            view.rx = Math.min(82, Math.max(10, view.rx - (event.clientY - view.lastY) * 0.3));
            view.lastX = event.clientX;
            view.lastY = event.clientY;
            view.idle = performance.now();
            apply();
        });
        const stop = () => {
            view.dragging = false;
            view.idle = performance.now();
        };
        container.addEventListener('pointerup', stop);
        container.addEventListener('pointercancel', stop);
        container.addEventListener('click', (event) => {
            if (event.target.closest('.k3d-picker') || view.moved > 6) {
                return;
            }
            const chainPressed = view.pressedChain;
            view.pressedChain = false;
            if (chainPressed || event.target.closest('.k3d-bead, .k3d-clasp, .k3d-eyelet')) {
                view.pressedCap = null;
                onCapPick?.('chain', event);

                return;
            }
            const cap = event.target.closest('.k3d-cap') || view.pressedCap;
            view.pressedCap = null;
            if (cap) {
                cap.classList.remove('is-pressed');
                void cap.offsetWidth;
                cap.classList.add('is-pressed');
                onCapPick?.(Number(cap.dataset.index), event);
            } else {
                onCapPick?.(null, event);
            }
        });

        if (!reducedMotion) {
            const tick = (time) => {
                if (!view.dragging && time - view.idle > 2500) {
                    apply(Math.sin(time / 1600) * 10);
                }
                requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        }

        return {
            render(chars, capColors, baseColor, missing, chainColor = '') {
                if (chars.length === 0) {
                    stage.innerHTML = '<span class="m-keychain__empty">Ketik tulisan untuk melihat pratinjau 3D.</span>';
                    stage.classList.add('is-empty');
                    hint.hidden = true;

                    return;
                }

                const { model, width } = buildModel(chars, capColors, baseColor, missing, chainColor);
                stage.classList.remove('is-empty');
                stage.replaceChildren(model);
                hint.hidden = false;
                view.scale = Math.min(1.35, (container.clientWidth * 0.72) / (width + 40));
                apply();
            },
        };
    };

    /* ---------------- Formulir rakit gantungan kunci ---------------- */
    const form = document.querySelector('[data-keychain-form]');

    if (form) {
        const catalog = JSON.parse(form.dataset.catalog || '{}');
        const field = (name) => form.querySelector(`[name="${name}"]`);
        const text = field('text');
        const quantity = field('quantity');
        const keycapColor = field('keycap_color');
        const baseColor = field('base_color');
        const switchItem = field('switch_item_id');
        const accessory = field('accessory_item_id');
        const letterBox = form.querySelector('[data-letter-colors]');
        const preview = form.querySelector('[data-preview]');
        let openPicker = () => {};
        const scene = createScene(preview, (index, event) => openPicker(index, event));
        const estimate = form.querySelector('[data-estimate]');
        const bigTotal = form.querySelector('[data-big-total]');
        const bigLabel = form.querySelector('[data-big-label]');
        const showBig = (label, amount, warn = false) => {
            if (bigTotal) {
                bigLabel.textContent = label;
                bigTotal.textContent = rupiah(amount);
                bigTotal.parentElement.classList.toggle('is-warn', warn);
            }
        };
        const colorNames = keycapColor ? [...keycapColor.options].map((option) => [option.value, option.text]) : [];
        let overrides = JSON.parse(letterBox?.dataset.old || '[]');
        let renderedLetters = null;

        const letters = () => text.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 10).split('');
        const colorAt = (index) => overrides[index] || keycapColor?.value || '';

        const renderLetterSelects = (chars) => {
            if (!letterBox || chars.join('') === renderedLetters) {
                return;
            }

            renderedLetters = chars.join('');
            letterBox.replaceChildren(...chars.map((char, index) => {
                const label = document.createElement('label');
                const select = document.createElement('select');
                select.name = `letter_colors[${index}]`;
                select.className = 'm-input';
                select.add(new Option('Sama dengan utama', ''));
                colorNames.forEach(([value, text]) => select.add(new Option(text, value, false, overrides[index] === value)));
                select.addEventListener('change', () => {
                    overrides[index] = select.value;
                    update();
                });
                label.append(`${index + 1}. ${char}`, select);

                return label;
            }));
        };

        /* Palet warna saat keycap di 3D diklik: hanya kombinasi yang punya keycap huruf itu. */
        const picker = document.createElement('div');
        picker.className = 'k3d-picker';
        picker.hidden = true;
        picker.setAttribute('role', 'dialog');
        preview.append(picker);

        const closePicker = () => {
            picker.hidden = true;
            preview.querySelectorAll('.k3d-cap.is-picking').forEach((cap) => cap.classList.remove('is-picking'));
        };

        const setOverride = (index, value) => {
            overrides[index] = value;
            const select = letterBox?.querySelector(`[name="letter_colors[${index}]"]`);
            if (select) {
                select.value = value;
            }
            closePicker();
            update();
        };

        openPicker = (index, event) => {
            if (index === null) {
                closePicker();

                return;
            }

            if (index === 'chain') {
                openChainPicker(event);

                return;
            }

            const chars = letters();
            const char = chars[index];
            const current = colorAt(index);
            const title = document.createElement('p');
            title.className = 'k3d-picker__title';
            title.textContent = `Huruf ${char} (ke-${index + 1})`;

            const options = colorNames
                .filter(([value]) => catalog.keycaps?.[value]?.[char])
                .map(([value, label]) => {
                    const entry = catalog.keycaps[value][char];
                    const { cap, letter } = splitVariant(value);
                    const capHex = swatch(cap);
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'k3d-picker__option' + (value === current ? ' is-current' : '');
                    button.disabled = entry.stock <= 0;
                    button.title = label;

                    const chip = document.createElement('span');
                    chip.className = 'k3d-picker__chip';
                    chip.style.background = capHex;
                    chip.style.color = letter ? swatch(letter) : inkFor(capHex);
                    chip.textContent = char;

                    const name = document.createElement('span');
                    name.className = 'k3d-picker__name';
                    name.textContent = label;
                    const stock = document.createElement('small');
                    stock.textContent = entry.stock > 0 ? `stok ${entry.stock}` : 'habis';
                    name.append(stock);

                    button.append(chip, name);
                    button.addEventListener('click', () => setOverride(index, value === keycapColor?.value ? '' : value));

                    return button;
                });

            if (options.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'k3d-picker__empty';
                empty.textContent = `Belum ada keycap "${char}" di stok.`;
                options.push(empty);
            }

            showPicker(event, title, options);
            preview.querySelectorAll('.k3d-cap').forEach((cap) => cap.classList.toggle('is-picking', Number(cap.dataset.index) === index));
        };

        const showPicker = (event, title, options) => {
            picker.replaceChildren(title, ...options);
            picker.hidden = false;

            const box = preview.getBoundingClientRect();
            const x = Math.min(Math.max(8, event.clientX - box.left - picker.offsetWidth / 2), box.width - picker.offsetWidth - 8);
            const y = Math.min(Math.max(8, event.clientY - box.top + 16), box.height - picker.offsetHeight - 8);
            picker.style.left = `${x}px`;
            picker.style.top = `${Math.max(8, y)}px`;
        };

        /* Palet rantai: aksesoris yang punya warna, plus pilihan tanpa rantai. */
        const openChainPicker = (event) => {
            if (!accessory) {
                return;
            }

            const title = document.createElement('p');
            title.className = 'k3d-picker__title';
            title.textContent = 'Warna rantai';

            const options = [...accessory.options].map((option) => {
                const entry = option.value ? catalog.items?.[option.value] : null;
                const color = option.dataset.color || '';
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'k3d-picker__option' + (option.value === accessory.value ? ' is-current' : '');
                button.disabled = entry ? entry.stock <= 0 : false;
                button.title = option.text;

                const chip = document.createElement('span');
                chip.className = 'k3d-picker__chip k3d-picker__chip--chain';
                const hex = color ? swatch(color) : '#d4d7de';
                chip.style.background = option.value
                    ? `radial-gradient(circle at 35% 30%, #ffffff 0%, ${hex} 45%, ${shade(hex, 0.55)} 100%)`
                    : 'repeating-linear-gradient(45deg, #e5e7eb 0 4px, #f8fafc 4px 8px)';

                const name = document.createElement('span');
                name.className = 'k3d-picker__name';
                name.textContent = option.value ? option.text.replace(/\s*\(Rp[^)]*\)\s*$/, '') : 'Tanpa rantai';
                const meta = document.createElement('small');
                meta.textContent = entry ? (entry.stock > 0 ? `${rupiah(entry.price)} · stok ${entry.stock}` : 'habis') : 'rantai silver bawaan';
                name.append(meta);

                button.append(chip, name);
                button.addEventListener('click', () => {
                    accessory.value = option.value;
                    closePicker();
                    update();
                });

                return button;
            });

            showPicker(event, title, options);
        };

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closePicker();
            }
        });
        document.addEventListener('pointerdown', (event) => {
            if (!preview.contains(event.target)) {
                closePicker();
            }
        });

        const row = (label, amount, muted = false) => {
            const line = document.createElement('div');
            line.className = muted ? 'm-row m-row--muted' : 'm-row';
            const left = document.createElement('span');
            const right = document.createElement('span');
            left.textContent = label;
            right.className = 'm-money';
            right.textContent = amount;
            line.append(left, right);

            return line;
        };

        const update = () => {
            const chars = letters();
            const count = Math.max(1, parseInt(quantity.value, 10) || 1);
            renderLetterSelects(chars);
            overrides = overrides.slice(0, chars.length);

            const capColors = chars.map((char, index) => colorAt(index));
            const missing = chars.map((char, index) => !catalog.keycaps?.[capColors[index]]?.[char]);
            scene.render(chars, capColors, baseColor?.value, missing, accessory?.selectedOptions[0]?.dataset.color || '');
            preview.append(picker);

            if (chars.length === 0) {
                estimate.innerHTML = '<p class="m-row--muted">Rincian harga muncul saat tulisan diisi.</p>';
                showBig('Harga jual', 0);

                return;
            }

            const problems = [];
            const lines = [];
            const need = {};
            const demand = (key, amount, stock, label) => {
                need[key] = need[key] || { amount: 0, stock, label };
                need[key].amount += amount;
            };

            const base = catalog.bases?.[baseColor.value]?.[chars.length];
            if (base) {
                lines.push([`Base ${chars.length} huruf · ${baseColor.value}`, base.price]);
                demand(`base-${baseColor.value}-${chars.length}`, count, base.stock, `Base ${chars.length} huruf ${baseColor.value}`);
            } else {
                problems.push(`Base ${chars.length} huruf warna ${baseColor.value} belum ada.`);
            }

            let capsTotal = 0;
            chars.forEach((char, index) => {
                const cap = catalog.keycaps?.[capColors[index]]?.[char];
                if (cap) {
                    capsTotal += cap.price;
                    demand(`cap-${capColors[index]}-${char}`, count, cap.stock, `Keycap ${char} ${variantLabel(capColors[index])}`);
                } else {
                    problems.push(`Keycap "${char}" warna ${variantLabel(capColors[index])} belum ada.`);
                }
            });
            lines.push([`${chars.length} keycap`, capsTotal]);

            const switchEntry = catalog.items?.[switchItem?.value];
            if (switchEntry) {
                lines.push([`${chars.length} switch`, switchEntry.price * chars.length]);
                demand(`item-${switchItem.value}`, chars.length * count, switchEntry.stock, 'Switch');
            }

            const accessoryEntry = catalog.items?.[accessory?.value];
            if (accessoryEntry) {
                lines.push(['Aksesoris', accessoryEntry.price]);
                demand(`item-${accessory.value}`, count, accessoryEntry.stock, 'Aksesoris');
            }

            Object.values(need).forEach((entry) => {
                if (entry.amount > entry.stock) {
                    problems.push(`Stok ${entry.label} kurang: butuh ${entry.amount}, sisa ${entry.stock}.`);
                }
            });

            const unitTotal = lines.reduce((sum, [, amount]) => sum + amount, 0);
            const nodes = lines.map(([label, amount]) => row(label, rupiah(amount), true));

            if (count > 1) {
                nodes.push(row('Harga per gantungan', rupiah(unitTotal)));
            }

            const total = row(count > 1 ? `Total ×${count}` : 'Harga jual', rupiah(unitTotal * count));
            total.classList.add('m-row--total');
            nodes.push(total);

            if (problems.length) {
                const warn = document.createElement('div');
                warn.className = 'm-warn';
                const list = document.createElement('ul');
                problems.forEach((problem) => {
                    const item = document.createElement('li');
                    item.textContent = problem;
                    list.append(item);
                });
                warn.append(list);
                nodes.push(warn);
            }

            estimate.replaceChildren(...nodes);
            showBig(count > 1 ? `Total ×${count}` : 'Harga jual', unitTotal * count, problems.length > 0);
        };

        text.addEventListener('input', () => {
            const cursor = text.selectionStart;
            text.value = text.value.toUpperCase().replace(/[^A-Z0-9 ]/g, '');
            text.setSelectionRange(cursor, cursor);
            update();
        });
        // Ganti warna utama = semua huruf kembali ke warna utama (pilihan per huruf direset).
        keycapColor?.addEventListener('change', () => {
            overrides = [];
            letterBox?.querySelectorAll('select').forEach((select) => {
                select.value = '';
            });
            closePicker();
        });
        [quantity, keycapColor, baseColor, switchItem, accessory].forEach((input) => input?.addEventListener('change', update));
        quantity.addEventListener('input', update);
        window.addEventListener('resize', update);
        update();
        text.focus({ preventScroll: true });
    }

    /* ---------------- Form item: tampilkan kolom sesuai kategori ---------------- */
    // Kolom yang tidak relevan disembunyikan dan dinonaktifkan supaya tidak ikut terkirim.
    document.querySelectorAll('[data-category-form]').forEach((categoryForm) => {
        const select = categoryForm.querySelector('[data-category-select]');
        const sync = () => {
            categoryForm.querySelectorAll('[data-show-for]').forEach((element) => {
                const visible = element.dataset.showFor.split(' ').includes(select.value);
                element.hidden = !visible;
                element.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.disabled = !visible;
                });
            });
        };

        select?.addEventListener('change', sync);
        sync();
    });

    /* ---------------- Kembalian ---------------- */
    const checkout = document.querySelector('[data-checkout]');

    if (checkout) {
        const total = parseInt(checkout.dataset.total, 10) || 0;
        const paid = checkout.querySelector('[name="paid_amount"]');
        const change = checkout.querySelector('[data-change]');
        const refresh = () => {
            const amount = parseInt(paid.value, 10);
            change.textContent = Number.isNaN(amount) ? rupiah(0) : (amount < total ? 'kurang ' + rupiah(total - amount) : rupiah(amount - total));
        };
        paid.addEventListener('input', refresh);
        refresh();
    }

    /* ---------------- Aksi massal item ---------------- */
    const bulkForm = document.querySelector('[data-bulk-form]');

    if (bulkForm) {
        const boxes = [...document.querySelectorAll('[data-bulk-item]')];
        const toggle = document.querySelector('[data-bulk-toggle]');
        const allItems = bulkForm.querySelector('[data-bulk-all]');
        const counter = bulkForm.querySelector('[data-bulk-count]');
        const refresh = () => {
            const selected = boxes.filter((box) => box.checked).length;
            counter.textContent = allItems.checked ? allItems.dataset.bulkAllLabel : `${selected} dipilih`;
            toggle.checked = selected > 0 && selected === boxes.length;
            toggle.indeterminate = selected > 0 && selected < boxes.length;
        };

        toggle?.addEventListener('change', () => {
            boxes.forEach((box) => { box.checked = toggle.checked; });
            refresh();
        });
        boxes.forEach((box) => box.addEventListener('change', refresh));
        allItems.addEventListener('change', refresh);
        bulkForm.addEventListener('submit', (event) => {
            const action = bulkForm.querySelector('[name="action"]');
            const target = allItems.checked ? allItems.dataset.bulkAllLabel : `${boxes.filter((box) => box.checked).length} item`;
            const verb = action.value === 'delete' ? 'Hapus' : 'Reset stok ke 0 untuk';

            if (!confirm(`${verb} ${target}? Tindakan ini tidak bisa dibatalkan.`)) {
                event.preventDefault();
            }
        });
        refresh();
    }
})();
