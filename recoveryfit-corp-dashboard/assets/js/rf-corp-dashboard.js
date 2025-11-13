(function () {
  'use strict';

  const docReady = (callback) => {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
    } else {
      callback();
    }
  };

  const formatNumber = (value) => {
    const number = Number(value || 0);
    return Number.isFinite(number) ? number.toLocaleString() : '0';
  };

  const formatDate = (value) => {
    if (!value) {
      return '—';
    }
    return value;
  };

  const buildMembershipSummary = (memberships) => {
    if (!Array.isArray(memberships) || memberships.length === 0) {
      return '—';
    }

    return memberships
      .map((membership) => membership.product_title || `#${membership.product_id}`)
      .filter(Boolean)
      .join(', ');
  };

  docReady(() => {
    const root = document.getElementById('rf-corp-dashboard-root');
    if (!root) {
      return;
    }

    const invoices = (window.RF_CORP_DATA && Array.isArray(window.RF_CORP_DATA.invoices))
      ? window.RF_CORP_DATA.invoices
      : [];

    root.innerHTML = '';

    const state = {
      invoices,
      invoiceFilter: 'all',
      searchTerm: '',
      dateFilter: 'all',
      sortMode: 'label',
    };

    const controls = document.createElement('div');
    controls.className = 'rf-controls';

    const invoiceFilterSelect = document.createElement('select');
    invoiceFilterSelect.className = 'rf-control-select';
    const invoiceOptions = new Set(['all']);
    invoices.forEach((invoice) => {
      if (invoice && invoice.invoice_label) {
        invoiceOptions.add(invoice.invoice_label);
      }
    });

    invoiceOptions.forEach((optionValue) => {
      const option = document.createElement('option');
      option.value = optionValue;
      option.textContent = optionValue === 'all'
        ? 'All Invoices'
        : optionValue;
      invoiceFilterSelect.appendChild(option);
    });

    invoiceFilterSelect.addEventListener('change', (event) => {
      state.invoiceFilter = event.target.value;
      render();
    });

    const searchInput = document.createElement('input');
    searchInput.type = 'search';
    searchInput.placeholder = 'Search parents or users…';
    searchInput.className = 'rf-control-search';
    searchInput.addEventListener('input', (event) => {
      state.searchTerm = event.target.value.trim().toLowerCase();
      render();
    });

    const dateFilterSelect = document.createElement('select');
    dateFilterSelect.className = 'rf-control-select';
    [
      { value: 'all', label: 'All Dates' },
      { value: '30', label: 'Last 30 Days' },
      { value: '60', label: 'Last 60 Days' },
      { value: '90', label: 'Last 90 Days' },
    ].forEach((item) => {
      const option = document.createElement('option');
      option.value = item.value;
      option.textContent = item.label;
      dateFilterSelect.appendChild(option);
    });

    dateFilterSelect.addEventListener('change', (event) => {
      state.dateFilter = event.target.value;
      render();
    });

    const sortSelect = document.createElement('select');
    sortSelect.className = 'rf-control-select';
    [
      { value: 'label', label: 'Sort: Invoice (A–Z)' },
      { value: 'logins', label: 'Sort: Total Logins' },
      { value: 'last_login', label: 'Sort: Last Login' },
    ].forEach((item) => {
      const option = document.createElement('option');
      option.value = item.value;
      option.textContent = item.label;
      sortSelect.appendChild(option);
    });

    sortSelect.addEventListener('change', (event) => {
      state.sortMode = event.target.value;
      render();
    });

    controls.appendChild(invoiceFilterSelect);
    controls.appendChild(searchInput);
    controls.appendChild(dateFilterSelect);
    controls.appendChild(sortSelect);

    const invoiceContainer = document.createElement('div');
    invoiceContainer.className = 'rf-invoice-container';

    root.appendChild(controls);
    root.appendChild(invoiceContainer);

    const matchesSearch = (invoice, searchTerm) => {
      if (!searchTerm) {
        return true;
      }

      const needle = searchTerm.toLowerCase();

      if (invoice.invoice_label && invoice.invoice_label.toLowerCase().includes(needle)) {
        return true;
      }

      if (!Array.isArray(invoice.parents)) {
        return false;
      }

      return invoice.parents.some((parent) => {
        if (parent.parent_label && parent.parent_label.toLowerCase().includes(needle)) {
          return true;
        }

        if (!Array.isArray(parent.sub_accounts)) {
          return false;
        }

        return parent.sub_accounts.some((user) => {
          const name = (user.name || '').toLowerCase();
          const email = (user.email || '').toLowerCase();
          const username = (user.username || '').toLowerCase();
          return name.includes(needle) || email.includes(needle) || username.includes(needle);
        });
      });
    };

    const passesDateFilter = (invoice) => {
      if (state.dateFilter === 'all') {
        return true;
      }

      const days = parseInt(state.dateFilter, 10);
      if (!Number.isFinite(days) || days <= 0) {
        return true;
      }

      const threshold = Date.now() / 1000 - days * 24 * 60 * 60;
      const lastTs = invoice.last_login_ts ? Number(invoice.last_login_ts) : 0;
      return lastTs && lastTs >= threshold;
    };

    const filterInvoices = () => {
      return state.invoices.filter((invoice) => {
        if (!invoice) {
          return false;
        }

        if (state.invoiceFilter !== 'all' && invoice.invoice_label !== state.invoiceFilter) {
          return false;
        }

        if (!passesDateFilter(invoice)) {
          return false;
        }

        if (!matchesSearch(invoice, state.searchTerm)) {
          return false;
        }

        return true;
      });
    };

    const sortInvoices = (list) => {
      const sorted = list.slice();

      if (state.sortMode === 'logins') {
        sorted.sort((a, b) => Number(b.total_logins || 0) - Number(a.total_logins || 0));
      } else if (state.sortMode === 'last_login') {
        sorted.sort((a, b) => Number(b.last_login_ts || 0) - Number(a.last_login_ts || 0));
      } else {
        sorted.sort((a, b) => {
          const labelA = (a.invoice_label || '').toLowerCase();
          const labelB = (b.invoice_label || '').toLowerCase();
          return labelA.localeCompare(labelB);
        });
      }

      return sorted;
    };

    const buildSubAccountTable = (parent) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'rf-subaccounts';
      wrapper.hidden = true;

      const table = document.createElement('table');
      table.className = 'rf-table';

      const thead = document.createElement('thead');
      thead.innerHTML = '<tr>' +
        '<th>Name</th>' +
        '<th>Email</th>' +
        '<th>Username</th>' +
        '<th>Login Count</th>' +
        '<th>Last Login</th>' +
        '<th>Memberships</th>' +
        '<th>Registered</th>' +
        '</tr>';
      table.appendChild(thead);

      const tbody = document.createElement('tbody');

      if (Array.isArray(parent.sub_accounts) && parent.sub_accounts.length > 0) {
        parent.sub_accounts.forEach((account) => {
          const tr = document.createElement('tr');
          if (account.user_id === parent.parent_user_id) {
            tr.classList.add('rf-parent-account-row');
          }

          const membershipSummary = buildMembershipSummary(account.memberships);

          tr.innerHTML =
            `<td>${account.name || '—'}</td>` +
            `<td>${account.email || '—'}</td>` +
            `<td>${account.username || '—'}</td>` +
            `<td>${formatNumber(account.login_count)}</td>` +
            `<td>${formatDate(account.last_login)}</td>` +
            `<td>${membershipSummary || '—'}</td>` +
            `<td>${formatDate(account.registered_at)}</td>`;

          tbody.appendChild(tr);
        });
      } else {
        const tr = document.createElement('tr');
        const td = document.createElement('td');
        td.colSpan = 7;
        td.textContent = 'No users found for this parent account.';
        tr.appendChild(td);
        tbody.appendChild(tr);
      }

      table.appendChild(tbody);
      wrapper.appendChild(table);
      return wrapper;
    };

    const buildParentList = (invoiceBody, parent) => {
      const parentItem = document.createElement('div');
      parentItem.className = 'rf-parent-item';

      const parentButton = document.createElement('button');
      parentButton.type = 'button';
      parentButton.className = 'rf-parent-header';
      parentButton.innerHTML =
        `<span class="rf-parent-label">${parent.parent_label || 'Unnamed Parent'}</span>` +
        `<span class="rf-parent-metric">Logins: ${formatNumber(parent.total_logins)}</span>` +
        `<span class="rf-parent-metric">Last Login: ${formatDate(parent.last_login)}</span>`;

      const subAccounts = buildSubAccountTable(parent);

      parentButton.addEventListener('click', () => {
        const willOpen = subAccounts.hidden;
        subAccounts.hidden = !willOpen;
        parentItem.classList.toggle('is-open', willOpen);
      });

      parentItem.appendChild(parentButton);
      parentItem.appendChild(subAccounts);
      invoiceBody.appendChild(parentItem);
    };

    const buildInvoiceCard = (invoice) => {
      const card = document.createElement('div');
      card.className = 'rf-invoice-card';

      const headerButton = document.createElement('button');
      headerButton.type = 'button';
      headerButton.className = 'rf-invoice-header';
      headerButton.innerHTML =
        `<span class="rf-invoice-label">${invoice.invoice_label || 'Unmapped'}</span>` +
        `<span class="rf-invoice-metric">Total Logins: ${formatNumber(invoice.total_logins)}</span>` +
        `<span class="rf-invoice-metric">Last Login: ${formatDate(invoice.last_login)}</span>`;

      const body = document.createElement('div');
      body.className = 'rf-invoice-body';
      body.hidden = true;

      if (Array.isArray(invoice.parents) && invoice.parents.length > 0) {
        invoice.parents.forEach((parent) => buildParentList(body, parent));
      } else {
        const empty = document.createElement('p');
        empty.textContent = 'No parent accounts found for this invoice group.';
        body.appendChild(empty);
      }

      headerButton.addEventListener('click', () => {
        const willOpen = body.hidden;
        body.hidden = !willOpen;
        card.classList.toggle('is-open', willOpen);
      });

      card.appendChild(headerButton);
      card.appendChild(body);
      return card;
    };

    const render = () => {
      invoiceContainer.innerHTML = '';

      const filtered = filterInvoices();
      const sorted = sortInvoices(filtered);

      if (sorted.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'rf-empty-state';
        empty.textContent = 'No invoice groups match the selected filters.';
        invoiceContainer.appendChild(empty);
        return;
      }

      sorted.forEach((invoice) => {
        invoiceContainer.appendChild(buildInvoiceCard(invoice));
      });
    };

    render();
  });
})();
