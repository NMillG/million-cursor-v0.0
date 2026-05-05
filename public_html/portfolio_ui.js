/* global Tabulator */

const RESERVED = new Set([
  "rownum",
  "_draft",
  "id",
  "ticker",
  "shares",
  "cost_per_share",
  "current_price",
  "total_cost",
  "market_value",
  "gain_loss_per_share",
  "gain_loss_per_share_pct",
  "purchase_date",
  "days_holding",
  "comments",
  "extras",
  "price_updated_at",
]);

let portfolioRowMenuEl = null;

function closePortfolioRowMenu() {
  if (portfolioRowMenuEl) {
    portfolioRowMenuEl.remove();
    portfolioRowMenuEl = null;
  }
  document.removeEventListener("keydown", onPortfolioRowMenuEscape);
}

function onPortfolioRowMenuEscape(e) {
  if (e.key === "Escape") closePortfolioRowMenu();
}

function onDocMouseDownPortfolioMenu(e) {
  if (portfolioRowMenuEl && !portfolioRowMenuEl.contains(e.target) && !e.target.closest(".portfolio-row-menu-btn")) {
    closePortfolioRowMenu();
  }
}

function money2(v) {
  if (v === null || v === undefined || Number.isNaN(v)) return "";
  return Number(v).toFixed(2);
}

function pct2(v) {
  if (v === null || v === undefined || Number.isNaN(v)) return "";
  return Number(v).toFixed(2);
}

function computeDerived(data) {
  const shares = Number(data.shares || 0);
  const cost = Number(data.cost_per_share || 0);
  const last =
    data.current_price === null || data.current_price === undefined || data.current_price === ""
      ? null
      : Number(data.current_price);

  const totalCost = shares * cost;
  data.total_cost = totalCost;

  if (last === null || Number.isNaN(last) || last <= 0) {
    data.market_value = null;
    data.gain_loss_per_share = null;
    data.gain_loss_per_share_pct = null;
    return data;
  }

  const mv = shares * last;
  const gl = last - cost;
  const glPct = cost !== 0 ? (gl / cost) * 100 : null;
  data.market_value = mv;
  data.gain_loss_per_share = gl;
  data.gain_loss_per_share_pct = glPct;
  return data;
}

async function api(action, payload = {}, withCsrf = true) {
  const body = { action, user_id: window.PORTFOLIO_CTX.userId, ...payload };
  if (withCsrf) body.csrf = window.PORTFOLIO_CTX.csrf;

  const res = await fetch("portfolio_api.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });

  const json = await res.json().catch(() => null);
  if (!json || json.ok !== true) {
    const msg = (json && json.error) || `Request failed (${res.status})`;
    throw new Error(msg);
  }
  return json;
}

function baseColumnDefs(readOnly) {
  const ro = !!readOnly;
  return {
    ticker: {
      title: "Ticker Symbol",
      field: "ticker",
      editor: ro ? false : "input",
      headerSort: false,
      widthGrow: 1,
      minWidth: 140,
      formatter: function (cell) {
        const raw = cell.getValue();
        const value = raw === null || raw === undefined ? "" : String(raw);
        if (ro) return value;
        const menuBtn = '<button type="button" class="portfolio-row-menu-btn" aria-label="Row menu" aria-haspopup="true">⋮</button>';
        const safe = value.replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;");
        return `<span class="portfolio-row-gutter">${menuBtn}<span class="portfolio-row-ticker">${safe}</span></span>`;
      },
      cellClick: function (e, cell) {
        if (ro) return;
        const btn = e.target && e.target.closest ? e.target.closest(".portfolio-row-menu-btn") : null;
        if (!btn) return;
        e.stopPropagation();
        openPortfolioRowMenu(btn, cell.getRow());
      },
    },
    shares: { title: "Number of Shares", field: "shares", editor: ro ? false : "number", headerSort: false, widthGrow: 1, minWidth: 120, mutator: (v, d) => { d.shares = Number(v); return d.shares; }, mutatorEdit: (v, d) => { d.shares = Number(v); return computeDerived(d).shares; } },
    cost_per_share: { title: "Cost per share ($)", field: "cost_per_share", editor: ro ? false : "number", headerSort: false, widthGrow: 1, minWidth: 130, mutatorEdit: (v, d) => { d.cost_per_share = Number(v); return computeDerived(d).cost_per_share; } },
    current_price: { title: "Current share price ($)", field: "current_price", editor: false, headerSort: false, widthGrow: 1, minWidth: 140, formatter: (c) => money2(c.getValue()) },
    total_cost: { title: "Total cost paid ($)", field: "total_cost", editor: false, headerSort: false, widthGrow: 1, minWidth: 130, formatter: (c) => money2(c.getValue()) },
    market_value: { title: "Current market value ($)", field: "market_value", editor: false, headerSort: false, widthGrow: 1, minWidth: 150, formatter: (c) => money2(c.getValue()) },
    gain_loss_per_share: { title: "Gain/loss per share ($)", field: "gain_loss_per_share", editor: false, headerSort: false, widthGrow: 1, minWidth: 150, formatter: (c) => money2(c.getValue()) },
    gain_loss_per_share_pct: { title: "Gain/loss per share (%)", field: "gain_loss_per_share_pct", editor: false, headerSort: false, widthGrow: 1, minWidth: 150, formatter: (c) => pct2(c.getValue()) },
    purchase_date: { title: "Purchase date", field: "purchase_date", editor: ro ? false : "input", headerSort: false, widthGrow: 1, minWidth: 130, mutatorEdit: (v, d) => { d.purchase_date = String(v); return d.purchase_date; } },
    days_holding: { title: "Total days holding", field: "days_holding", editor: false, headerSort: false, widthGrow: 1, minWidth: 130 },
    comments: { title: "Comments", field: "comments", editor: ro ? false : "textarea", headerSort: false, widthGrow: 2, minWidth: 160 },
  };
}

function buildEmptyDraft(prefs) {
  const row = {
    _draft: true,
    ticker: "",
    shares: 0,
    cost_per_share: 0,
    purchase_date: new Date().toISOString().slice(0, 10),
    comments: "",
  };
  const custom = Array.isArray(prefs.customColumns) ? prefs.customColumns : [];
  for (const cc of custom) {
    const f = cc && cc.field ? String(cc.field) : "";
    if (f) row[f] = "";
  }
  return computeDerived({ ...row });
}

function openPortfolioRowMenu(anchorBtn, row) {
  closePortfolioRowMenu();
  const d = row.getData();
  const menu = document.createElement("div");
  menu.className = "portfolio-row-menu-dropdown";
  menu.setAttribute("role", "menu");

  function addItem(label, fn, danger) {
    const b = document.createElement("button");
    b.type = "button";
    b.className = danger ? "portfolio-row-menu-item portfolio-row-menu-item-danger" : "portfolio-row-menu-item";
    b.setAttribute("role", "menuitem");
    b.textContent = label;
    b.addEventListener("click", (ev) => {
      ev.stopPropagation();
      closePortfolioRowMenu();
      fn();
    });
    menu.appendChild(b);
  }

  if (d._draft) {
    addItem("Save", () => void saveDraftRow(row));
    addItem("Cancel", () => row.delete());
  } else {
    addItem("Move up", () => moveRowByDelta(row, -1));
    addItem("Move down", () => moveRowByDelta(row, 1));
    addItem("Delete", () => void deletePersistedRow(row), true);
  }

  document.body.appendChild(menu);
  portfolioRowMenuEl = menu;

  const rect = anchorBtn.getBoundingClientRect();
  const mw = 200;
  menu.style.position = "fixed";
  menu.style.left = `${Math.max(8, Math.min(rect.left, window.innerWidth - mw - 8))}px`;
  menu.style.top = `${rect.bottom + 4}px`;
  menu.style.zIndex = "10050";
  menu.style.minWidth = `${mw}px`;

  document.addEventListener("mousedown", onDocMouseDownPortfolioMenu, true);
  document.addEventListener("keydown", onPortfolioRowMenuEscape);
}

function moveRowByDelta(row, delta) {
  if (!table) return;
  const rows = table.getRows();
  const i = rows.indexOf(row);
  if (i < 0) return;
  const j = i + delta;
  if (j < 0 || j >= rows.length) return;
  const data = table.getData();
  const tmp = data[i];
  data[i] = data[j];
  data[j] = tmp;
  table.setData(data);
  void persistRowOrder();
}

async function persistRowOrder() {
  if (!table || window.PORTFOLIO_CTX.readOnly) return;
  const ids = [];
  for (const r of table.getRows()) {
    const d = r.getData();
    if (d._draft) continue;
    const id = Number(d.id);
    if (Number.isFinite(id) && id > 0) ids.push(id);
  }
  latestPrefs = { ...(latestPrefs || {}), rowOrder: ids };
  try {
    await api("save_prefs", { prefs: latestPrefs });
  } catch (err) {
    window.alert(err && err.message ? err.message : String(err));
  }
}

function buildColumnsFromPrefs(prefs, readOnly) {
  const defs = baseColumnDefs(readOnly);
  const order = Array.isArray(prefs.columnOrder) ? prefs.columnOrder : [];
  const hidden = new Set(Array.isArray(prefs.hidden) ? prefs.hidden : []);

  const cols = [];

  for (const field of order) {
    if (!defs[field]) continue;
    const c = { ...defs[field], visible: !hidden.has(field) };
    cols.push(c);
  }

  const custom = Array.isArray(prefs.customColumns) ? prefs.customColumns : [];
  for (const cc of custom) {
    const field = String(cc.field || "");
    const title = String(cc.title || field);
    if (!field) continue;
    cols.push({
      title,
      field,
      editor: readOnly ? false : "input",
      headerSort: false,
      widthGrow: 1,
      minWidth: 140,
      visible: !hidden.has(field),
    });
  }

  return cols;
}

function prefsFromTable(table, existingPrefs) {
  const prefs = JSON.parse(JSON.stringify(existingPrefs || {}));
  prefs.version = 1;
  prefs.columnOrder = [];
  prefs.hidden = [];

  const cols = table.getColumns();
  for (const col of cols) {
    const def = col.getDefinition();
    const field = def.field;
    if (!field) continue;
    prefs.columnOrder.push(field);
    if (!col.isVisible()) prefs.hidden.push(field);
  }

  if (Array.isArray(existingPrefs.rowOrder)) {
    prefs.rowOrder = existingPrefs.rowOrder;
  }

  return prefs;
}

function extrasFromRowData(data) {
  const extras = {};
  for (const [k, v] of Object.entries(data)) {
    if (RESERVED.has(k)) continue;
    if (v === null || v === undefined || v === "") continue;
    extras[k] = v;
  }
  return extras;
}

async function saveDraftRow(row) {
  if (!table) return;
  const d = row.getData();
  if (!d._draft) return;
  computeDerived(d);
  try {
    const extras = extrasFromRowData(d);
    const resp = await api("create_row", {
      ticker: String(d.ticker || "").trim(),
      shares: Number(d.shares),
      cost_per_share: Number(d.cost_per_share),
      purchase_date: String(d.purchase_date || "").trim(),
      comments: String(d.comments || "").trim(),
      extras,
    });
    await table.replaceData(resp.rows.map((r) => computeDerived({ ...r })));
    void persistRowOrder();
  } catch (err) {
    window.alert(err && err.message ? err.message : String(err));
  }
}

async function deletePersistedRow(row) {
  if (!table) return;
  const d = row.getData();
  if (d._draft) return;
  const rowId = Number(d.id);
  if (!Number.isFinite(rowId) || rowId <= 0) return;
  if (!window.confirm("Delete this entire row permanently?")) return;
  try {
    const resp = await api("delete_row", { id: rowId });
    await table.replaceData(resp.rows.map((r) => computeDerived({ ...r })));
    void persistRowOrder();
  } catch (err) {
    window.alert(err && err.message ? err.message : String(err));
  }
}

async function persistPrefs(table, prefs) {
  await api("save_prefs", { prefs });
}

async function persistPrefsFromTable(table) {
  const prefs = prefsFromTable(table, latestPrefs || {});
  await persistPrefs(table, prefs);
}

async function updateRowRemote(row) {
  const d = row.getData();
  const payload = {
    id: d.id,
    ticker: d.ticker,
    shares: d.shares,
    cost_per_share: d.cost_per_share,
    purchase_date: d.purchase_date,
    comments: d.comments,
    extras: extrasFromRowData(d),
  };
  const resp = await api("update_row", payload);
  return resp.rows;
}

let table = null;
let latestPrefs = null;

async function loadAll() {
  const resp = await api("list", {}, false);
  latestPrefs = resp.prefs;
  document.getElementById("marketHint").textContent = resp.tiingo
    ? "Live prices: Tiingo"
    : "Live prices: unavailable (missing TIINGO_API_KEY or blocked outbound HTTPS)";

  if (!table) {
    const readOnly = window.PORTFOLIO_CTX.readOnly;
    table = new Tabulator("#portfolioGrid", {
      height: "min(70vh, 720px)",
      layout: "fitColumns",
      movableColumns: true,
      movableRows: !readOnly,
      movableRowsHandle: false,
      reactiveData: true,
      placeholder: "No positions yet. Click “Add ticker” to enter a full row, then Save.",
      columns: buildColumnsFromPrefs(resp.prefs, readOnly),
      rowFormatter: function (row) {
        const el = row.getElement();
        if (row.getData()._draft) el.classList.add("portfolio-row-draft");
        else el.classList.remove("portfolio-row-draft");
      },
      columnDefaults: {
        headerMenu: [
          {
            label: "Hide column",
            action: async function (e, column) {
              if (window.PORTFOLIO_CTX.readOnly) return;
              column.hide();
              await persistPrefsFromTable(table);
            },
          },
        ],
      },
      data: resp.rows.map((r) => computeDerived({ ...r })),
    });

    table.on("cellEdited", async function (cell) {
      if (window.PORTFOLIO_CTX.readOnly) return;
      const row = cell.getRow();
      const field = cell.getField();
      if (!field) return;

      const d = row.getData();
      if (d._draft) {
        computeDerived(row.getData());
        return;
      }

      computeDerived(row.getData());

      const rowsData = await updateRowRemote(row);
      await table.replaceData(rowsData.map((r) => computeDerived({ ...r })));
    });

    table.on("columnMoved", async function () {
      if (window.PORTFOLIO_CTX.readOnly) return;
      const prefs = prefsFromTable(table, latestPrefs);
      latestPrefs = prefs;
      await persistPrefs(table, prefs);
    });

    table.on("rowMoved", function () {
      closePortfolioRowMenu();
      void persistRowOrder();
    });

    if (!readOnly) {
      document.getElementById("btnRefreshPrices").addEventListener("click", async () => {
        const resp2 = await api("refresh_prices");
        await table.replaceData(resp2.rows.map((r) => computeDerived({ ...r })));
      });

      document.getElementById("btnAddTicker").addEventListener("click", () => {
        if (!table) return;
        const rows = table.getRows();
        for (let i = 0; i < rows.length; i += 1) {
          if (rows[i].getData()._draft) {
            try {
              rows[i].getElement().scrollIntoView({ behavior: "smooth", block: "nearest" });
            } catch (_) {}
            return;
          }
        }
        const draft = buildEmptyDraft(latestPrefs || {});
        table.addRow(draft, "top").then((r) => {
          try {
            r.getElement().scrollIntoView({ behavior: "smooth", block: "nearest" });
          } catch (_) {}
        });
      });

      document.getElementById("btnAddColumn").addEventListener("click", async () => {
        const field = prompt("New column field key (letters/numbers/underscore; will be prefixed with x_)?", "notes");
        if (!field) return;
        const title = prompt("Column title?", "Notes");
        if (!title) return;
        const resp2 = await api("add_custom_column", { field, title });
        latestPrefs = resp2.prefs;
        table.setColumns(buildColumnsFromPrefs(resp2.prefs, false));
        const resp3 = await api("list", {}, false);
        await table.replaceData(resp3.rows.map((r) => computeDerived({ ...r })));
      });

      document.getElementById("btnRemoveColumn").addEventListener("click", async () => {
        const field = prompt("Custom column field to remove (must match column field, usually x_...)?", "");
        if (!field) return;
        const resp2 = await api("remove_custom_column", { field });
        latestPrefs = resp2.prefs;
        table.setColumns(buildColumnsFromPrefs(resp2.prefs, false));
        await table.replaceData(resp2.rows.map((r) => computeDerived({ ...r })));
      });
    }
  } else {
    table.setColumns(buildColumnsFromPrefs(resp.prefs, window.PORTFOLIO_CTX.readOnly));
    await table.replaceData(resp.rows.map((r) => computeDerived({ ...r })));
  }
}

loadAll().catch((err) => {
  document.getElementById("portfolioGrid").textContent = String(err && err.message ? err.message : err);
});
