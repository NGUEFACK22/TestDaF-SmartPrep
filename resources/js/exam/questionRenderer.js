/**
 * QuestionRenderer — moteur générique d'affichage des questions.
 *
 * Une seule fabrique gère tous les types (aucune page séparée par type).
 * `question.type` détermine l'interface ; `read()` renvoie la réponse normalisée.
 */
export function renderQuestion(question, existing = null) {
    const el = document.createElement('div');
    el.className = 'question-block rounded-lg border border-slate-200 bg-white p-4 mb-4';
    el.dataset.questionId = question.id;
    el.dataset.questionType = question.type;

    if (question.prompt) {
        const p = document.createElement('p');
        p.className = 'font-medium text-slate-800 mb-3';
        p.textContent = question.prompt;
        el.appendChild(p);
    }

    const body = document.createElement('div');
    el.appendChild(body);

    const spec = buildSpec(question, existing, body);
    el._read = spec.read;

    return el;
}

function buildSpec(q, existing, body) {
    const options = q.answer_options || [];
    const data = q.data || {};

    switch (q.type) {
        case 'single_choice':
        case 'true_false':
            return singleChoice(body, options, existing);
        case 'multiple_choice':
            return multiChoice(body, options, existing);
        case 'fill_blank':
            return fillBlank(body, data.gaps || existing, existing);
        case 'short_answer':
        case 'text_input':
            return textInput(body, existing, q.type === 'text_input');
        case 'ordering':
            return ordering(body, data.items || options, existing);
        case 'matching':
        case 'pair_assignment':
            return matching(body, data.pairs || options, existing, q);
        case 'category_assignment':
            return categoryAssignment(body, data.categories || [], data.items || options, existing);
        default:
            return textInput(body, existing, false);
    }
}

function singleChoice(body, options, existing) {
    const name = `q_${Math.random().toString(36).slice(2)}`;
    options.forEach((opt) => {
        const value = String(opt.label || opt.id);
        const label = document.createElement('label');
        label.className = 'question-option flex items-center gap-3 border border-slate-200 rounded-lg p-3 mb-2 cursor-pointer';
        label.innerHTML = `<input type="radio" name="${name}" value="${escapeHtml(value)}" class="h-4 w-4"> <span>${escapeHtml(opt.label ? opt.label + ' — ' : '')}${escapeHtml(opt.text)}</span>`;
        const input = label.querySelector('input');
        if (existing !== null && String(existing) === value) {
            input.checked = true;
            label.classList.add('selected');
        }
        input.addEventListener('change', () => {
            body.querySelectorAll('.question-option').forEach((n) => n.classList.remove('selected'));
            label.classList.add('selected');
        });
        body.appendChild(label);
    });

    return { read: () => body.querySelector('input:checked')?.value ?? null };
}

function multiChoice(body, options, existing) {
    const selected = Array.isArray(existing) ? existing.map(String) : [];
    options.forEach((opt) => {
        const value = String(opt.label || opt.id);
        const label = document.createElement('label');
        label.className = 'question-option flex items-center gap-3 border border-slate-200 rounded-lg p-3 mb-2 cursor-pointer';
        label.innerHTML = `<input type="checkbox" value="${escapeHtml(value)}" class="h-4 w-4" ${selected.includes(value) ? 'checked' : ''}> <span>${escapeHtml(opt.label ? opt.label + ' — ' : '')}${escapeHtml(opt.text)}</span>`;
        const input = label.querySelector('input');
        if (input.checked) label.classList.add('selected');
        input.addEventListener('change', () => label.classList.toggle('selected', input.checked));
        body.appendChild(label);
    });

    return { read: () => Array.from(body.querySelectorAll('input:checked')).map((i) => i.value) };
}

function fillBlank(body, gaps, existing) {
    const values = Array.isArray(existing) ? existing : [];
    const count = Math.max(1, Array.isArray(gaps) ? gaps.length : 1);
    for (let i = 0; i < count; i += 1) {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'w-full border border-slate-300 rounded-lg p-2 mb-2';
        input.placeholder = `Lücke ${i + 1}`;
        input.value = values[i] ?? '';
        body.appendChild(input);
    }

    return { read: () => Array.from(body.querySelectorAll('input')).map((i) => i.value) };
}

function textInput(body, existing, long) {
    const el = long ? document.createElement('textarea') : document.createElement('input');
    if (!long) el.type = 'text';
    el.className = 'w-full border border-slate-300 rounded-lg p-2';
    el.value = typeof existing === 'string' ? existing : '';
    body.appendChild(el);

    return { read: () => el.value };
}

function ordering(body, items, existing) {
    const list = document.createElement('ul');
    list.className = 'space-y-2';
    items.forEach((item, index) => {
        const li = document.createElement('li');
        li.className = 'flex items-center gap-2 border border-slate-200 rounded-lg p-2 bg-white cursor-move';
        li.draggable = true;
        li.dataset.value = item.value ?? item.id ?? index;
        li.innerHTML = `<span class="text-slate-400">⋮⋮</span> <span>${escapeHtml(item.text || item)}</span>`;
        list.appendChild(li);
    });
    body.appendChild(list);
    enableDragReorder(list);

    return { read: () => Array.from(list.children).map((li) => li.dataset.value) };
}

function matching(body, pairs, existing) {
    const wrap = document.createElement('div');
    wrap.className = 'space-y-2';
    const map = (existing && typeof existing === 'object' && !Array.isArray(existing)) ? existing : {};

    const rights = pairs.map((p) => ({
        value: p.right_value ?? p.right ?? p.value ?? p,
        label: p.right_label ?? p.right ?? p.value ?? p,
    }));

    pairs.forEach((pair) => {
        const leftKey = pair.left ?? pair.key ?? pair;
        const row = document.createElement('div');
        row.className = 'flex items-center gap-3';

        const select = document.createElement('select');
        select.className = 'border border-slate-300 rounded-lg p-2 flex-1 matching-select';
        select.dataset.left = leftKey;
        select.innerHTML = '<option value="">—</option>'
            + rights.map((r) => `<option value="${escapeHtml(r.value)}">${escapeHtml(r.label)}</option>`).join('');
        if (map[leftKey] !== undefined) select.value = map[leftKey];

        row.innerHTML = `<span class="w-1/2">${escapeHtml(pair.left_label ?? pair.left ?? pair)}</span>`;
        row.appendChild(select);
        wrap.appendChild(row);
    });

    body.appendChild(wrap);

    return {
        read: () => {
            const out = {};
            wrap.querySelectorAll('select.matching-select').forEach((s) => { out[s.dataset.left] = s.value; });
            return out;
        },
    };
}

function categoryAssignment(body, categories, items, existing) {
    const map = (existing && typeof existing === 'object' && !Array.isArray(existing)) ? existing : {};
    const wrap = document.createElement('div');
    wrap.className = 'space-y-2';

    items.forEach((item) => {
        const itemKey = item.value ?? item.id ?? item;
        const row = document.createElement('div');
        row.className = 'flex items-center gap-3';

        const select = document.createElement('select');
        select.className = 'border border-slate-300 rounded-lg p-2 category-select';
        select.dataset.item = itemKey;
        select.innerHTML = '<option value="">—</option>'
            + categories.map((c) => `<option value="${escapeHtml(c.value ?? c)}">${escapeHtml(c.label ?? c)}</option>`).join('');
        if (map[itemKey] !== undefined) select.value = map[itemKey];

        row.innerHTML = `<span class="w-1/2">${escapeHtml(item.text ?? item)}</span>`;
        row.appendChild(select);
        wrap.appendChild(row);
    });

    body.appendChild(wrap);

    return {
        read: () => {
            const out = {};
            wrap.querySelectorAll('select.category-select').forEach((s) => { out[s.dataset.item] = s.value; });
            return out;
        },
    };
}

function enableDragReorder(list) {
    let dragged = null;
    list.addEventListener('dragstart', (e) => { dragged = e.target.closest('li'); });
    list.addEventListener('dragover', (e) => {
        e.preventDefault();
        const target = e.target.closest('li');
        if (!target || target === dragged) return;
        const rect = target.getBoundingClientRect();
        const after = e.clientY > rect.top + rect.height / 2;
        list.insertBefore(dragged, after ? target.nextSibling : target);
    });
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}