const state = {
    lookups: {},
    users: [],
    cards: [],
    history: [],
    selectedCardId: null,
};

const api = {
    async get(url) {
        const res = await fetch(url);
        return res.json();
    },
    async send(url, method, body) {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        return res.json();
    },
};

const elements = {
    usersList: document.getElementById('usersList'),
    cardsList: document.getElementById('cardsList'),
    historyList: document.getElementById('historyList'),
    departmentsList: document.getElementById('departmentsList'),
    positionsList: document.getElementById('positionsList'),
    resourcesList: document.getElementById('resourcesList'),
    internetResourcesList: document.getElementById('internetResourcesList'),
    softwareList: document.getElementById('softwareList'),
    statusFilter: document.getElementById('statusFilter'),
};

const userModal = document.getElementById('userModal');
const userForm = document.getElementById('userForm');
const userModalTitle = document.getElementById('userModalTitle');

const cardModal = document.getElementById('cardModal');
const cardForm = document.getElementById('cardForm');
const cardModalTitle = document.getElementById('cardModalTitle');

const selectById = (id) => document.getElementById(id);

function openModal(modal) {
    modal.classList.add('open');
}

function closeModal(modal) {
    modal.classList.remove('open');
}

function setOptions(select, items, placeholder = 'Выберите...') {
    select.innerHTML = '';
    if (!select.multiple) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;
        select.appendChild(option);
    }
    items.forEach((item) => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = item.name;
        select.appendChild(option);
    });
}

function setList(el, items) {
    el.innerHTML = '';
    items.forEach((item) => {
        const li = document.createElement('li');
        li.textContent = item.name;
        el.appendChild(li);
    });
}

function renderUsers() {
    elements.usersList.innerHTML = '';
    state.users.forEach((user) => {
        const item = document.createElement('div');
        item.className = 'list-item';

        item.innerHTML = `
            <div class="title">${user.full_name}</div>
            <div class="meta">${user.department_name || 'Без отдела'} · ${user.position_name || 'Без должности'}</div>
            <div class="meta">Статус: ${user.is_terminated ? 'уволен' : 'активен'}</div>
            <div class="meta">Роли: ${user.is_manager ? 'Руководитель' : ''} ${user.is_director ? 'Директор' : ''} ${user.is_executor ? 'Исполнитель' : ''}</div>
            <div class="actions">
                <button data-edit-user="${user.id}">Редактировать</button>
                <button data-copy-user="${user.id}">Копировать</button>
                <button data-delete-user="${user.id}">Удалить</button>
            </div>
        `;
        elements.usersList.appendChild(item);
    });

    populateUserSelects();
}

function renderCards() {
    elements.cardsList.innerHTML = '';
    state.cards.forEach((card) => {
        const item = document.createElement('div');
        item.className = 'list-item';
        const abs1 = card.abs1_access ? 'Да' : 'Нет';
        const abs2 = card.abs2_access ? 'Да' : 'Нет';

        item.innerHTML = `
            <div class="title">${card.full_name}</div>
            <div class="meta">АБС1: ${abs1} · АБС2: ${abs2}</div>
            <div class="actions">
                <button data-view-history="${card.id}">История</button>
                <button data-edit-card="${card.id}">Редактировать</button>
                <button data-print-card="${card.id}">Печать</button>
                <button data-delete-card="${card.id}">Удалить</button>
            </div>
        `;
        elements.cardsList.appendChild(item);
    });
}

function renderHistory() {
    elements.historyList.innerHTML = '';
    if (!state.history.length) {
        elements.historyList.innerHTML = '<div class="meta">История пока пуста</div>';
        return;
    }

    state.history.forEach((entry) => {
        const item = document.createElement('div');
        item.className = 'history-item';
        const details = entry.details ? JSON.parse(entry.details) : {};
        item.innerHTML = `
            <div><strong>${entry.action}</strong> · ${new Date(entry.created_at).toLocaleString('ru-RU')}</div>
            <pre>${JSON.stringify(details, null, 2)}</pre>
        `;
        elements.historyList.appendChild(item);
    });
}

function populateUserSelects() {
    const managerSelect = selectById('managerSelect');
    const cardUserSelect = selectById('cardUserSelect');
    const userOptions = state.users.map((user) => ({ id: user.id, name: user.full_name }));
    setOptions(managerSelect, userOptions, 'Не назначен');
    setOptions(cardUserSelect, userOptions, 'Выберите пользователя');
}

async function loadLookups() {
    const data = await api.get('/api/lookups');
    state.lookups = data;
    setList(elements.departmentsList, data.departments || []);
    setList(elements.positionsList, data.positions || []);
    setList(elements.resourcesList, data.resources || []);
    setList(elements.internetResourcesList, data.internetResources || []);
    setList(elements.softwareList, data.software || []);

    setOptions(selectById('departmentSelect'), data.departments || [], 'Выберите отдел');
    setOptions(selectById('positionSelect'), data.positions || [], 'Выберите должность');
    setOptions(selectById('resourcesSelect'), data.resources || []);
    setOptions(selectById('internetResourcesSelect'), data.internetResources || []);
    setOptions(selectById('softwareSelect'), data.software || []);
}

async function loadUsers() {
    const status = elements.statusFilter.value;
    const data = await api.get(`/api/users?status=${status}`);
    state.users = data.users || [];
    renderUsers();
}

async function loadCards() {
    const data = await api.get('/api/cards');
    state.cards = data.cards || [];
    renderCards();
}

async function loadHistory(cardId) {
    const data = await api.get(`/api/cards/${cardId}/history`);
    state.history = data.history || [];
    renderHistory();
}

function resetUserForm() {
    userForm.reset();
    selectById('userId').value = '';
    selectById('isExecutor').checked = true;
}

function resetCardForm() {
    cardForm.reset();
    selectById('cardId').value = '';
}

function openUserModal(user = null) {
    resetUserForm();
    if (user) {
        userModalTitle.textContent = 'Редактировать пользователя';
        selectById('userId').value = user.id;
        selectById('fullName').value = user.full_name;
        selectById('departmentSelect').value = user.department_id || '';
        selectById('positionSelect').value = user.position_id || '';
        selectById('managerSelect').value = user.manager_id || '';
        selectById('terminated').checked = user.is_terminated;
        selectById('isManager').checked = user.is_manager;
        selectById('isDirector').checked = user.is_director;
        selectById('isExecutor').checked = user.is_executor;
    } else {
        userModalTitle.textContent = 'Добавить пользователя';
    }
    openModal(userModal);
}

async function openCardModal(cardId = null) {
    resetCardForm();
    if (cardId) {
        cardModalTitle.textContent = 'Редактировать карточку';
        const data = await api.get(`/api/cards/${cardId}`);
        const card = data.card;
        selectById('cardId').value = card.id;
        selectById('cardUserSelect').value = card.user_id;
        setMultiSelect('resourcesSelect', card.resources);
        setMultiSelect('internetResourcesSelect', card.internet_resources);
        setMultiSelect('softwareSelect', card.software);
        selectById('abs1Access').checked = card.abs1_access;
        selectById('abs2Access').checked = card.abs2_access;
    } else {
        cardModalTitle.textContent = 'Добавить карточку';
    }
    openModal(cardModal);
}

function setMultiSelect(id, values) {
    const select = selectById(id);
    const ids = values.map((item) => String(item.id));
    Array.from(select.options).forEach((option) => {
        option.selected = ids.includes(option.value);
    });
}

function getSelectedOptions(id) {
    return Array.from(selectById(id).selectedOptions).map((option) => option.value).filter(Boolean);
}

userForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = {
        full_name: selectById('fullName').value.trim(),
        department_id: selectById('departmentSelect').value || null,
        position_id: selectById('positionSelect').value || null,
        manager_id: selectById('managerSelect').value || null,
        is_terminated: selectById('terminated').checked,
        is_manager: selectById('isManager').checked,
        is_director: selectById('isDirector').checked,
        is_executor: selectById('isExecutor').checked,
    };
    const id = selectById('userId').value;
    if (id) {
        await api.send(`/api/users/${id}`, 'PUT', payload);
    } else {
        await api.send('/api/users', 'POST', payload);
    }
    closeModal(userModal);
    await loadUsers();
    await loadCards();
});

cardForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = {
        user_id: selectById('cardUserSelect').value,
        resources: getSelectedOptions('resourcesSelect'),
        internet_resources: getSelectedOptions('internetResourcesSelect'),
        software: getSelectedOptions('softwareSelect'),
        abs1_access: selectById('abs1Access').checked,
        abs2_access: selectById('abs2Access').checked,
    };
    const id = selectById('cardId').value;
    if (id) {
        await api.send(`/api/cards/${id}`, 'PUT', payload);
    } else {
        await api.send('/api/cards', 'POST', payload);
    }
    closeModal(cardModal);
    await loadCards();
});

window.addEventListener('click', (event) => {
    if (event.target.matches('[data-close]')) {
        closeModal(userModal);
        closeModal(cardModal);
    }
    if (event.target === userModal) {
        closeModal(userModal);
    }
    if (event.target === cardModal) {
        closeModal(cardModal);
    }
});

document.getElementById('addUserBtn').addEventListener('click', () => openUserModal());
document.getElementById('addCardBtn').addEventListener('click', () => openCardModal());

elements.statusFilter.addEventListener('change', () => loadUsers());

document.addEventListener('click', async (event) => {
    const target = event.target;
    if (target.matches('[data-edit-user]')) {
        const user = state.users.find((u) => String(u.id) === target.dataset.editUser);
        if (user) {
            openUserModal(user);
        }
    }
    if (target.matches('[data-copy-user]')) {
        await api.send(`/api/users/${target.dataset.copyUser}/copy`, 'POST', {});
        await loadUsers();
    }
    if (target.matches('[data-delete-user]')) {
        await api.send(`/api/users/${target.dataset.deleteUser}`, 'DELETE', {});
        await loadUsers();
        await loadCards();
    }
    if (target.matches('[data-edit-card]')) {
        await openCardModal(target.dataset.editCard);
    }
    if (target.matches('[data-delete-card]')) {
        await api.send(`/api/cards/${target.dataset.deleteCard}`, 'DELETE', {});
        await loadCards();
    }
    if (target.matches('[data-view-history]')) {
        state.selectedCardId = target.dataset.viewHistory;
        await loadHistory(state.selectedCardId);
    }
    if (target.matches('[data-print-card]')) {
        window.open(`/api/cards/${target.dataset.printCard}/print`, '_blank');
    }
});

async function init() {
    await loadLookups();
    await loadUsers();
    await loadCards();
}

init();
