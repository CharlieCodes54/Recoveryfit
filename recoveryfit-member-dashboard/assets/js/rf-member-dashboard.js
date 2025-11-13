(function () {
  const data = window.RF_MEMBER_DATA || { members: [], totals: {}, strings: {} };
  const __ = window.wp && window.wp.i18n ? window.wp.i18n.__ : (str) => str;
  const root = document.getElementById('rf-member-dashboard-root');

  if (!root) {
    return;
  }

  const state = {
    search: '',
    membership: 'all',
    dateRange: 'all',
    sort: 'logins',
  };

  const members = Array.isArray(data.members) ? data.members : [];
  const totals = data.totals || {};
  const strings = data.strings || {};

  const layout = document.createElement('div');
  layout.className = 'rf-md-layout';

  layout.appendChild(buildSummary(totals));
  layout.appendChild(buildControls());

  const resultsContainer = document.createElement('div');
  resultsContainer.className = 'rf-md-results';
  layout.appendChild(resultsContainer);

  root.innerHTML = '';
  root.appendChild(layout);

  renderResults();

  /**
   * Build the summary row of cards.
   */
  function buildSummary(summary) {
    const wrapper = document.createElement('div');
    wrapper.className = 'rf-md-summary';

    const cards = [
      {
        label: __('Total Members', 'recoveryfit-member-dashboard'),
        value: formatNumber(summary.total_members || 0),
        accent: 'primary',
      },
      {
        label: __('Active (30 days)', 'recoveryfit-member-dashboard'),
        value: formatNumber(summary.active_30 || 0),
        accent: 'success',
      },
      {
        label: __('Never Logged In', 'recoveryfit-member-dashboard'),
        value: formatNumber(summary.never_logged_in || 0),
        accent: 'warning',
      },
      {
        label: __('Total Login Events', 'recoveryfit-member-dashboard'),
        value: formatNumber(summary.total_login_events || 0),
        accent: 'purple',
      },
    ];

    cards.forEach((card) => {
      const el = document.createElement('div');
      el.className = `rf-md-card rf-md-card--${card.accent}`;
      el.innerHTML = `
        <span class="rf-md-card__label">${card.label}</span>
        <span class="rf-md-card__value">${card.value}</span>
      `;
      wrapper.appendChild(el);
    });

    return wrapper;
  }

  /**
   * Build filter + sort controls.
   */
  function buildControls() {
    const controls = document.createElement('div');
    controls.className = 'rf-md-controls';

    const searchWrap = document.createElement('label');
    searchWrap.className = 'rf-md-control';
    searchWrap.innerHTML = `
      <span>${__('Search', 'recoveryfit-member-dashboard')}</span>
      <input type="search" placeholder="${__('Name, email, or username…', 'recoveryfit-member-dashboard')}" />
    `;
    const searchInput = searchWrap.querySelector('input');
    searchInput.addEventListener('input', (event) => {
      state.search = event.target.value.toLowerCase();
      renderResults();
    });

    const membershipWrap = document.createElement('label');
    membershipWrap.className = 'rf-md-control';
    membershipWrap.innerHTML = `
      <span>${__('Membership', 'recoveryfit-member-dashboard')}</span>
      <select></select>
    `;
    const membershipSelect = membershipWrap.querySelector('select');
    const membershipOptions = ['all', ...getMembershipOptions()];
    membershipOptions.forEach((value) => {
      const option = document.createElement('option');
      option.value = value;
      option.textContent = value === 'all' ? __('All memberships', 'recoveryfit-member-dashboard') : value;
      membershipSelect.appendChild(option);
    });
    membershipSelect.addEventListener('change', (event) => {
      state.membership = event.target.value;
      renderResults();
    });

    const dateWrap = document.createElement('label');
    dateWrap.className = 'rf-md-control';
    dateWrap.innerHTML = `
      <span>${__('Last Login', 'recoveryfit-member-dashboard')}</span>
      <select>
        <option value="all">${__('All time', 'recoveryfit-member-dashboard')}</option>
        <option value="7">${__('Last 7 days', 'recoveryfit-member-dashboard')}</option>
        <option value="30">${__('Last 30 days', 'recoveryfit-member-dashboard')}</option>
        <option value="60">${__('Last 60 days', 'recoveryfit-member-dashboard')}</option>
        <option value="90">${__('Last 90 days', 'recoveryfit-member-dashboard')}</option>
      </select>
    `;
    const dateSelect = dateWrap.querySelector('select');
    dateSelect.addEventListener('change', (event) => {
      state.dateRange = event.target.value;
      renderResults();
    });

    const sortWrap = document.createElement('label');
    sortWrap.className = 'rf-md-control';
    sortWrap.innerHTML = `
      <span>${__('Sort by', 'recoveryfit-member-dashboard')}</span>
      <select>
        <option value="logins">${__('Logins (high → low)', 'recoveryfit-member-dashboard')}</option>
        <option value="last_login">${__('Last login (newest)', 'recoveryfit-member-dashboard')}</option>
        <option value="name">${__('Name (A → Z)', 'recoveryfit-member-dashboard')}</option>
        <option value="registered">${__('Join date (newest)', 'recoveryfit-member-dashboard')}</option>
      </select>
    `;
    const sortSelect = sortWrap.querySelector('select');
    sortSelect.addEventListener('change', (event) => {
      state.sort = event.target.value;
      renderResults();
    });

    controls.append(searchWrap, membershipWrap, dateWrap, sortWrap);
    return controls;
  }

  /**
   * Render filtered member list.
   */
  function renderResults() {
    resultsContainer.innerHTML = '';
    const filtered = applyFilters();

    if (!filtered.length) {
      const empty = document.createElement('div');
      empty.className = 'rf-md-empty';
      empty.textContent = strings.noMembers || __('No members match the current filters.', 'recoveryfit-member-dashboard');
      resultsContainer.appendChild(empty);
      return;
    }

    filtered.forEach((member) => {
      const details = document.createElement('details');
      details.className = 'rf-md-member';

      const summary = document.createElement('summary');
      summary.className = 'rf-md-member__summary';
      summary.innerHTML = `
        <span class="rf-md-member__name">${escapeHtml(member.display_name || member.username)}</span>
        <span class="rf-md-member__meta">
          <span class="rf-md-chip">${__('Logins', 'recoveryfit-member-dashboard')}: ${formatNumber(member.login_count)}</span>
          <span class="rf-md-chip">${__('Last login', 'recoveryfit-member-dashboard')}: ${formatDate(member.last_login)}</span>
          <span class="rf-md-chip">${__('Joined', 'recoveryfit-member-dashboard')}: ${formatDate(member.registered_at)}</span>
        </span>
      `;
      details.appendChild(summary);

      const body = document.createElement('div');
      body.className = 'rf-md-member__body';
      body.innerHTML = `
        <div class="rf-md-member__info">
          <div><strong>${__('Email', 'recoveryfit-member-dashboard')}:</strong> <a href="mailto:${escapeAttr(member.email)}">${escapeHtml(member.email)}</a></div>
          <div><strong>${__('Username', 'recoveryfit-member-dashboard')}:</strong> ${escapeHtml(member.username)}</div>
          <div><strong>${__('Role(s)', 'recoveryfit-member-dashboard')}:</strong> ${escapeHtml(member.role || '—')}</div>
          <div><strong>${__('Total logins', 'recoveryfit-member-dashboard')}:</strong> ${formatNumber(member.login_count)}</div>
          <div><strong>${__('Last login', 'recoveryfit-member-dashboard')}:</strong> ${formatDate(member.last_login)}</div>
        </div>
        ${renderMemberships(member.memberships)}
      `;
      details.appendChild(body);

      resultsContainer.appendChild(details);
    });
  }

  /**
   * Apply search, filter, and sort to members.
   */
  function applyFilters() {
    const searchTerm = state.search.trim();
    const membershipFilter = state.membership;
    const dateFilter = state.dateRange;
    const sortBy = state.sort;

    let filtered = members.filter((member) => {
      if (searchTerm) {
        const blob = [member.display_name, member.email, member.username]
          .join(' ') // combine fields
          .toLowerCase();
        if (!blob.includes(searchTerm)) {
          return false;
        }
      }

      if (membershipFilter !== 'all') {
        const titles = (member.memberships || []).map((m) => m.product_title || '');
        if (!titles.includes(membershipFilter)) {
          return false;
        }
      }

      if (dateFilter !== 'all') {
        const days = parseInt(dateFilter, 10);
        const threshold = Date.now() - days * 24 * 60 * 60 * 1000;
        if (!member.last_login_ts || member.last_login_ts * 1000 < threshold) {
          return false;
        }
      }

      return true;
    });

    filtered = filtered.sort((a, b) => {
      switch (sortBy) {
        case 'name':
          return (a.display_name || '').localeCompare(b.display_name || '');
        case 'last_login':
          return (b.last_login_ts || 0) - (a.last_login_ts || 0);
        case 'registered':
          return new Date(b.registered_at).getTime() - new Date(a.registered_at).getTime();
        case 'logins':
        default:
          return (b.login_count || 0) - (a.login_count || 0);
      }
    });

    return filtered;
  }

  /**
   * Render membership list markup.
   */
  function renderMemberships(memberships) {
    const list = Array.isArray(memberships) ? memberships : [];
    if (!list.length) {
      return `<div class="rf-md-member__memberships">${__('No active memberships recorded.', 'recoveryfit-member-dashboard')}</div>`;
    }

    const items = list
      .map((membership) => {
        const title = escapeHtml(membership.product_title || __('Unknown product', 'recoveryfit-member-dashboard'));
        const status = escapeHtml(membership.status || '');
        const created = membership.created_at ? formatDate(membership.created_at) : '—';
        return `
          <li>
            <span class="rf-md-membership__title">${title}</span>
            <span class="rf-md-membership__meta">${__('Status', 'recoveryfit-member-dashboard')}: ${status || __('n/a', 'recoveryfit-member-dashboard')} · ${__('Since', 'recoveryfit-member-dashboard')}: ${created}</span>
          </li>
        `;
      })
      .join('');

    return `<div class="rf-md-member__memberships"><h4>${__('Active memberships', 'recoveryfit-member-dashboard')}</h4><ul>${items}</ul></div>`;
  }

  /**
   * Collect unique membership titles.
   */
  function getMembershipOptions() {
    const titles = new Set();
    members.forEach((member) => {
      (member.memberships || []).forEach((membership) => {
        if (membership.product_title) {
          titles.add(membership.product_title);
        }
      });
    });
    return Array.from(titles).sort((a, b) => a.localeCompare(b));
  }

  function formatNumber(value) {
    const number = Number(value) || 0;
    return number.toLocaleString();
  }

  function formatDate(value) {
    if (!value) {
      return '—';
    }

    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) {
      return value;
    }

    return date.toLocaleString();
  }

  function escapeHtml(str) {
    if (str === null || str === undefined) {
      return '';
    }
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function escapeAttr(str) {
    return escapeHtml(str).replace(/"/g, '&quot;');
  }
})();
