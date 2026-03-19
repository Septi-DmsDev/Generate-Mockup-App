// --- INITIALIZATION ---
const canvas = document.getElementById('editorCanvas');
const ctx = canvas.getContext('2d');

let layers = [];
let activeLayerId = null;
let isDragging = false;
let dragStart = { x: 0, y: 0 };

// History Stacks
let historyStack = [];
let redoStack = [];
const MAX_HISTORY = 20;

// Config Canvas Size
canvas.width = 1800;
canvas.height = 1800;

// Default Props
const defaultProps = {
    scale: 1, rotation: 0, opacity: 1,
    brightness: 100, contrast: 100,
    flipX: 1, flipY: 1
};

// --- CORE FUNCTIONS ---

// 1. Load Images saat Start
async function init() {
    try {
        console.log("Loading assets...", appConfig);

        // A. Load Background User (Wajib & Locked)
        if(appConfig.bg) {
            await addLayer('background', appConfig.bg, "Background User", { locked: true, x: 900, y: 900 });
        }
        
        // B. Load Preset / Produk (Movable)
        if(appConfig.productPath) {
            await addLayer('product', appConfig.productPath, "Preset Overlay", { x: 900, y: 900 });
        }
        
        // C. Load Logo (Dinamis Sesuai Pilihan User)
        if(appConfig.logoPath) {
            const logoL = await addLayer('logo', appConfig.logoPath, "Logo");
            
            if(logoL) {
                logoL.scale = 1.0; 

                const padding = 60;     
                const canvasW = 1800;
                const canvasH = 1800;
                
                const halfW = (logoL.width * logoL.scale) / 2;
                const halfH = (logoL.height * logoL.scale) / 2;

                const pos = appConfig.logoPos || 'top-right';

                switch(pos) {
                    case 'top-left':
                        logoL.x = padding + halfW; logoL.y = padding + halfH; break;
                    case 'top-right':
                        logoL.x = canvasW - padding - halfW; logoL.y = padding + halfH; break;
                    case 'bottom-left':
                        logoL.x = padding + halfW; logoL.y = canvasH - padding - halfH; break;
                    case 'bottom-right':
                        logoL.x = canvasW - padding - halfW; logoL.y = canvasH - padding - halfH; break;
                    case 'center':
                        logoL.x = canvasW / 2; logoL.y = canvasH / 2; break;
                    default: 
                        logoL.x = canvasW - padding - halfW; logoL.y = padding + halfH;
                }
                saveHistory();
            }
        }
        
        render();
        updateLayerList();
        updateUI(); 

    } catch (e) {
        console.error("Error loading assets:", e);
        alert("Gagal memuat beberapa aset gambar. Cek console untuk detail.");
    }
}

// 2. Add Layer Logic
function addLayer(type, srcOrText, name, customProps = {}) {
    return new Promise((resolve) => {
        const id = Date.now() + Math.random();
        
        const layer = {
            id: id,
            type: type,
            name: name,
            x: customProps.x || canvas.width/2,
            y: customProps.y || canvas.height/2,
            ...defaultProps,
            ...customProps
        };

        if(type === 'text') {
            // Logika TEXT (Wajib Lengkap)
            layer.text = srcOrText;
            layer.font = customProps.font || "Arial";
            layer.fontSize = customProps.fontSize || 60;
            layer.color = customProps.color || "#000000";
            
            layers.push(layer);
            setActiveLayer(id);
            saveHistory();
            updateLayerList();
            render();
            updateUI();
            resolve(layer);
        } else {
            // Logika GAMBAR (Background, Product, Logo, Image/Stiker)
            const img = new Image();
            img.crossOrigin = "anonymous";
            img.src = srcOrText;
            
            img.onload = () => {
                layer.img = img;
                layer.width = img.width;
                layer.height = img.height;

                // Auto Fit logic jika scale tidak ditentukan
                if (typeof customProps.scale === 'undefined') {
                    const canvasW = canvas.width;  
                    const canvasH = canvas.height; 
                    const scaleX = canvasW / layer.width;
                    const scaleY = canvasH / layer.height;
                    layer.scale = Math.min(scaleX, scaleY);
                }

                layers.push(layer);
                
                if(!layer.locked) setActiveLayer(id);
                
                saveHistory();
                render(); 
                updateUI(); 
                updateLayerList(); 
                resolve(layer);
            };
            
            img.onerror = () => {
                console.warn("Image not found: " + srcOrText);
                resolve(null);
            };
        }
    });
}

// 3. Render Loop
function render() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    layers.forEach(layer => {
        ctx.save();
        
        ctx.translate(layer.x, layer.y);
        ctx.rotate(layer.rotation * Math.PI / 180);
        ctx.scale(layer.scale * layer.flipX, layer.scale * layer.flipY);
        
        ctx.globalAlpha = layer.opacity;
        ctx.filter = `brightness(${layer.brightness}%) contrast(${layer.contrast}%)`;

        if (layer.type === 'text') {
            ctx.font = `${layer.fontSize}px "${layer.font}"`;
            ctx.fillStyle = layer.color;
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText(layer.text, 0, 0);
        } else if (layer.img) {
            ctx.drawImage(layer.img, -layer.width/2, -layer.height/2, layer.width, layer.height);
        }

        // Selection Border
        if (layer.id === activeLayerId) {
            ctx.filter = "none"; 
            ctx.lineWidth = 2 / layer.scale; 
            ctx.strokeStyle = "#2563eb";
            
            if(layer.type === 'text') {
                const metrics = ctx.measureText(layer.text);
                const h = layer.fontSize; 
                ctx.strokeRect(-metrics.width/2 - 10, -h/2 - 5, metrics.width + 20, h + 10);
            } else {
                ctx.strokeRect(-layer.width/2, -layer.height/2, layer.width, layer.height);
            }
        }
        
        ctx.restore();
    });
}

// --- EVENTS & INTERACTION ---

function getMousePos(evt) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    return {
        x: (evt.clientX - rect.left) * scaleX,
        y: (evt.clientY - rect.top) * scaleY
    };
}

function getLayerAt(x, y) {
    for (let i = layers.length - 1; i >= 0; i--) {
        const L = layers[i];
        if (L.locked) continue;

        const dx = x - L.x;
        const dy = y - L.y;
        const dist = Math.sqrt(dx*dx + dy*dy);

        let radius = 0;
        if(L.type === 'text') {
            radius = (L.fontSize * L.text.length * 0.4) * L.scale; 
        } else {
            radius = (Math.max(L.width, L.height) / 2) * L.scale;
        }

        if (dist <= radius) return L.id;
    }
    return null;
}

canvas.addEventListener('mousedown', (e) => {
    const pos = getMousePos(e);
    const clickedId = getLayerAt(pos.x, pos.y);

    if (clickedId) {
        setActiveLayer(clickedId);
        isDragging = true;
        const layer = layers.find(l => l.id === clickedId);
        dragStart = { x: pos.x - layer.x, y: pos.y - layer.y };
    }
});

canvas.addEventListener('mousemove', (e) => {
    if (isDragging && activeLayerId) {
        const pos = getMousePos(e);
        const layer = layers.find(l => l.id === activeLayerId);
        layer.x = pos.x - dragStart.x;
        layer.y = pos.y - dragStart.y;
        render();
    }
});

canvas.addEventListener('mouseup', () => {
    if (isDragging) {
        isDragging = false;
        saveHistory();
    }
});

// --- TOOLS FUNCTIONS ---

function setActiveLayer(id) {
    activeLayerId = id;
    updateUI();
    render();
    updateLayerList();
}

function updateUI() {
    const L = layers.find(l => l.id === activeLayerId);
    const panel = document.getElementById('propertiesPanel');
    const msg = document.getElementById('noSelectionMsg');
    const textTools = document.getElementById('textTools');

    if (!L) {
        panel.style.display = 'none';
        msg.style.display = 'block';
        return;
    }

    panel.style.display = 'block';
    msg.style.display = 'none';

    document.getElementById('rngScale').value = L.scale;
    document.getElementById('valScale').value = L.scale.toFixed(2);
    
    document.getElementById('rngRotate').value = L.rotation;
    document.getElementById('valRotate').value = L.rotation;

    document.getElementById('rngOpacity').value = L.opacity;
    document.getElementById('rngBrightness').value = L.brightness;
    document.getElementById('rngContrast').value = L.contrast;

    if (L.type === 'text') {
        textTools.style.display = 'block';
        document.getElementById('inpText').value = L.text;
        document.getElementById('inpFont').value = L.font;
        document.getElementById('inpColor').value = L.color;
    } else {
        textTools.style.display = 'none';
    }
}

// Binding Inputs
document.getElementById('rngScale').oninput = (e) => updateProp('scale', parseFloat(e.target.value));
document.getElementById('valScale').onchange = (e) => updateProp('scale', parseFloat(e.target.value));

document.getElementById('rngRotate').oninput = (e) => updateProp('rotation', parseInt(e.target.value));
document.getElementById('valRotate').onchange = (e) => updateProp('rotation', parseInt(e.target.value));

document.getElementById('rngOpacity').oninput = (e) => updateProp('opacity', parseFloat(e.target.value));
document.getElementById('rngBrightness').oninput = (e) => updateProp('brightness', parseInt(e.target.value));
document.getElementById('rngContrast').oninput = (e) => updateProp('contrast', parseInt(e.target.value));

document.getElementById('inpText').oninput = (e) => updateProp('text', e.target.value);
document.getElementById('inpFont').onchange = (e) => updateProp('font', e.target.value);
document.getElementById('inpColor').oninput = (e) => updateProp('color', e.target.value);

document.getElementById('btnMirrorX').onclick = () => toggleMirror('x');
document.getElementById('btnMirrorY').onclick = () => toggleMirror('y');

function updateProp(key, value) {
    const L = layers.find(l => l.id === activeLayerId);
    if (L) {
        L[key] = value;
        if(key === 'scale') {
            document.getElementById('rngScale').value = value;
            document.getElementById('valScale').value = value.toFixed(2);
        }
        if(key === 'rotation') {
            document.getElementById('rngRotate').value = value;
            document.getElementById('valRotate').value = value;
        }
        render();
    }
}

function toggleMirror(axis) {
    const L = layers.find(l => l.id === activeLayerId);
    if (L) {
        if(axis === 'x') L.flipX *= -1;
        if(axis === 'y') L.flipY *= -1;
        saveHistory();
        render();
    }
}

// Add Objects
function addTextLayer() {
    addLayer('text', 'Teks Baru', 'Layer Teks');
}

// UPLOAD GAMBAR TAMBAHAN (IMAGE/STIKER)
document.getElementById('imgUpload').onchange = function(e) {
    const file = e.target.files[0];
    if(!file) return;
    const reader = new FileReader();
    reader.onload = function(event) {
        // Tipe: 'image' (bukan 'product')
        addLayer('image', event.target.result, 'Gambar Upload');
    };
    reader.readAsDataURL(file);
    e.target.value = ''; 
};

function updateLayerList() {
    const list = document.getElementById('layerList');
    list.innerHTML = '';
    
    [...layers].reverse().forEach((L) => {
        const li = document.createElement('li');
        li.className = `layer-item ${L.id === activeLayerId ? 'active' : ''}`;
        
        li.onclick = (e) => {
            if (e.target.tagName !== 'BUTTON') {
                if(!L.locked) setActiveLayer(L.id);
            }
        };
        
        let icon = '📷';
        if (L.type === 'text') icon = 'T';
        if (L.type === 'background' && L.id === layers[0].id) icon = '🌄'; 

        const lockIcon = L.locked ? '🔒' : '🔓';
        const lockColor = L.locked ? '#d9534f' : '#aaa'; 

        const isMainBG = (L.id === layers[0].id && L.type === 'background');
        const deleteBtn = isMainBG ? '' : `
            <button onclick="deleteLayerInList('${L.id}', event)" title="Hapus Layer" 
                    style="border:none; background:none; cursor:pointer; font-size:14px; color:#666;">
                🗑️
            </button>
        `;

        li.innerHTML = `
            <div style="display:flex; align-items:center; gap:10px; flex:1; overflow:hidden;">
                <span style="font-size:16px;">${icon}</span>
                <span class="layer-name" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:13px; color:${L.locked ? '#999' : '#333'}">
                    ${L.name || L.type}
                </span>
            </div>
            
            <div style="display:flex; gap:5px; align-items:center;">
                <button onclick="toggleLock('${L.id}', event)" title="${L.locked ? 'Buka Kunci' : 'Kunci Layer'}"
                        style="border:none; background:none; cursor:pointer; font-size:14px; color:${lockColor};">
                    ${lockIcon}
                </button>
                ${deleteBtn}
            </div>
        `;
        list.appendChild(li);
    });
}

function moveLayer(dir) {
    if(!activeLayerId) return;
    const index = layers.findIndex(l => l.id === activeLayerId);
    if(index === -1) return;

    if(dir === 'up' && index < layers.length - 1) {
        [layers[index], layers[index+1]] = [layers[index+1], layers[index]];
    } else if (dir === 'down' && index > 0) {
        [layers[index], layers[index-1]] = [layers[index-1], layers[index]];
    }
    
    saveHistory();
    render();
    updateLayerList();
}

function deleteLayerInList(id, event) {
    if(event) event.stopPropagation(); 
    
    if(confirm("Hapus layer ini?")) {
        if(activeLayerId === id) activeLayerId = null;
        layers = layers.filter(l => l.id != id); 
        saveHistory();
        render();
        updateLayerList();
    }
}

function toggleLock(id, event) {
    if(event) event.stopPropagation(); 
    
    const L = layers.find(l => l.id == id);
    if(L) {
        L.locked = !L.locked; 
        if(L.locked && activeLayerId === id) {
            activeLayerId = null;
            updateUI(); 
        }
        saveHistory();
        render();         
        updateLayerList(); 
    }
}

// --- HISTORY SYSTEM (Undo/Redo) ---
function saveHistory() {
    const state = layers.map(l => ({...l})); 
    historyStack.push(state);
    if (historyStack.length > MAX_HISTORY) historyStack.shift();
    redoStack = []; 
}

function editorUndo() {
    if(historyStack.length > 1) {
        redoStack.push(historyStack.pop()); 
        const prevState = historyStack[historyStack.length - 1];
        restoreState(prevState);
    }
}

function editorRedo() {
    if(redoStack.length > 0) {
        const nextState = redoStack.pop();
        historyStack.push(nextState);
        restoreState(nextState);
    }
}

function restoreState(savedLayers) {
    layers = savedLayers.map(savedL => savedL);
    render();
    updateLayerList();
}

function fitToPage() {
    if(!activeLayerId) return;
    const L = layers.find(l => l.id === activeLayerId);
    
    if(!L || L.locked) return;

    const canvasW = 1800; 
    const canvasH = 1800;

    L.x = canvasW / 2;
    L.y = canvasH / 2;
    L.rotation = 0; 

    if (L.width > 0 && L.height > 0) {
        const scaleX = canvasW / L.width;
        const scaleY = canvasH / L.height;
        L.scale = Math.min(scaleX, scaleY); 
    }

    saveHistory();
    render();
    updateUI(); 
}

// --- EXPORT JSON & SAVE (PERBAIKAN UTAMA) ---
function saveLayoutJSON() {
    const exportData = layers.map(L => {
        return {
            id: L.id,
            type: L.type,   
            name: L.name,
            x: L.x, y: L.y, scale: L.scale, rotation: L.rotation,
            flipX: L.flipX, flipY: L.flipY,
            opacity: L.opacity, brightness: L.brightness, contrast: L.contrast,
            
            // Text Property
            text: L.text || null,
            font: L.font || null,
            fontSize: L.fontSize || null,
            color: L.color || null,
            
            // Image Property (Src / Base64)
            // PENTING: Ambil src dari properti L.img.src agar Base64 terkirim
            src: (L.type !== 'text' && L.img) ? L.img.src : null,
            
            width: L.width, height: L.height
        };
    });

    const btnSimpan = document.querySelector('.btn-save');
    const oldText = btnSimpan.innerText;
    btnSimpan.innerText = "⏳ Menyimpan...";
    btnSimpan.disabled = true;

    const formData = new FormData();
    formData.append('layout_data', JSON.stringify(exportData));

    // Cek Endpoint (Edit Master atau Edit Satuan)
    let endpoint = 'save_master.php'; 
    if(typeof appConfig !== 'undefined' && appConfig.targetFilename) {
        endpoint = 'save_single_result.php'; // Mode Edit Satuan
        formData.append('target_file', appConfig.targetFilename);
    }

    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            if(endpoint === 'save_master.php') {
                alert("✅ Master Layout Tersimpan!\n\nSilakan kembali ke menu utama, pilih Preset & Logo yang sama, upload banyak gambar, lalu tekan tombol 'Proses Gambar'.");
                window.location = "user_index.php"; 
            } else {
                alert("✅ Gambar berhasil diupdate!");
                // Refresh page
                window.location = "process_generate.php?t=" + new Date().getTime(); 
            }
        } else {
            alert("Gagal menyimpan: " + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Terjadi kesalahan koneksi.");
    })
    .finally(() => {
        btnSimpan.innerText = oldText;
        btnSimpan.disabled = false;
    });
}
