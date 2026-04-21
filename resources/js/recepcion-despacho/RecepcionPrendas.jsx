import React, { useState, useRef } from 'react';

const TWEAK_DEFAULTS = {
  theme: 'light',
  accent: '#2563eb',
  density: 'normal',
  view: 'list',
};

// Icons as React components
function IconCheck() {
  return (
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
      <circle cx="9" cy="9" r="9" fill="currentColor" />
      <path d="M4.5 9l3 3 6-6" stroke="#fff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

function IconPending() {
  return (
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
      <circle cx="9" cy="9" r="8" stroke="currentColor" strokeWidth="1.8" />
      <path d="M9 5v4l2.5 2.5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
    </svg>
  );
}

function IconBox() {
  return (
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
      <path d="M3 7l7-4 7 4v6l-7 4-7-4V7z" stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round" />
      <path d="M3 7l7 4 7-4M10 11v6" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
    </svg>
  );
}

function IconFilter() {
  return (
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
      <path d="M2 4h14M5 9h8M7.5 14h3" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
    </svg>
  );
}

function IconScan() {
  return (
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
      <path d="M2 6V3a1 1 0 011-1h3M14 2h3a1 1 0 011 1v3M18 14v3a1 1 0 01-1 1h-3M6 18H3a1 1 0 01-1-1v-3" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
      <path d="M2 10h16" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
    </svg>
  );
}

function formatFechaHora(iso) {
  if (!iso) return null;
  const d = new Date(iso);
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  let hours = d.getHours();
  const ampm = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12 || 12;
  const hoursStr = String(hours).padStart(2, '0');
  const minutes = String(d.getMinutes()).padStart(2, '0');
  return `${day}/${month}/${year} ${hoursStr}:${minutes} ${ampm}`;
}

// Card component
function PrendaCard({ item, onConfirm, onNovedades, accent, animatingId }) {
  const isRecibido = item.status === 'recibido';
  const isAnimating = animatingId === item.id;
  const totalUds = item.tallas.reduce((s, t) => s + t.cantidad, 0);
  const pad = '18px 16px';

  return (
    <div
      style={{
        background: isRecibido ? '#f0fdf4' : '#ffffff',
        borderRadius: 18,
        padding: pad,
        marginBottom: 12,
        boxShadow: isAnimating
          ? `0 0 0 3px ${accent}55, 0 4px 16px rgba(0,0,0,0.08)`
          : isRecibido
            ? '0 2px 8px rgba(22,163,74,0.1)'
            : '0 2px 8px rgba(0,0,0,0.06)',
        border: isRecibido ? '1.5px solid #bbf7d0' : '1.5px solid #f0f0f0',
        transition: 'all 0.35s cubic-bezier(0.4,0,0.2,1)',
        position: 'relative',
        overflow: 'hidden',
      }}
    >
      {/* Accent bar left */}
      <div
        style={{
          position: 'absolute',
          left: 0,
          top: 14,
          bottom: 14,
          width: 3.5,
          borderRadius: '0 3px 3px 0',
          background: isRecibido ? '#16a34a' : accent,
          transition: 'background 0.35s',
        }}
      />

      {/* Header row */}
      <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 4, paddingLeft: 10 }}>
        <div style={{ flex: 1 }}>
          <div
            style={{
              fontSize: 11,
              fontWeight: 700,
              letterSpacing: '0.06em',
              textTransform: 'uppercase',
              color: isRecibido ? '#16a34a' : accent,
              marginBottom: 2,
            }}
          >
            {item.cliente}
          </div>
          <div style={{ fontSize: 17, fontWeight: 800, color: '#111827', lineHeight: 1.25 }}>
            {item.prenda}
          </div>
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 4, marginLeft: 10, flexShrink: 0 }}>
          <div
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: 5,
              padding: '4px 10px',
              borderRadius: 20,
              background: isRecibido ? '#dcfce7' : '#eff6ff',
              color: isRecibido ? '#16a34a' : accent,
              fontSize: 11,
              fontWeight: 700,
            }}
          >
            {isRecibido ? <IconCheck /> : <IconPending />}
            {isRecibido ? 'Recibido' : 'Pendiente'}
          </div>
          {item.tipoEntrega && (
            <div
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: 4,
                padding: '3px 8px',
                borderRadius: 999,
                fontSize: 10,
                fontWeight: 700,
                letterSpacing: '0.04em',
                textTransform: 'uppercase',
                background: item.tipoEntrega === 'parcial' ? '#fff7ed' : '#ecfdf5',
                color: item.tipoEntrega === 'parcial' ? '#c2410c' : '#047857',
                border: item.tipoEntrega === 'parcial' ? '1px solid #fed7aa' : '1px solid #a7f3d0',
              }}
            >
              {item.tipoEntrega === 'parcial' ? 'Parcial' : 'Completo'}
            </div>
          )}
          {item.fechaEntrega && (
            <div style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 10, color: '#6b7280', fontWeight: 600 }}>
              <svg width="11" height="11" viewBox="0 0 11 11" fill="none">
                <circle cx="5.5" cy="5.5" r="4.5" stroke="#6b7280" strokeWidth="1.4" />
                <path d="M5.5 3v2.5l1.5 1" stroke="#6b7280" strokeWidth="1.4" strokeLinecap="round" />
              </svg>
              Entregado (Supervisor): {formatFechaHora(item.fechaEntrega)}
            </div>
          )}
          {item.fechaLlegada && (
            <div style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 10, color: '#f59e0b', fontWeight: 600 }}>
              <svg width="11" height="11" viewBox="0 0 11 11" fill="none">
                <circle cx="5.5" cy="5.5" r="4.5" stroke="#f59e0b" strokeWidth="1.4" />
                <path d="M5.5 3v2.5l1.5 1" stroke="#f59e0b" strokeWidth="1.4" strokeLinecap="round" />
              </svg>
              {formatFechaHora(item.fechaLlegada)}
            </div>
          )}
        </div>
      </div>

      {/* Descripción */}
      <div
        style={{
          paddingLeft: 10,
          marginBottom: 12,
          fontSize: 12,
          color: '#6b7280',
          fontStyle: 'italic',
          lineHeight: 1.4,
        }}
      >
        {item.descripcion}
      </div>

      {/* Tallas grid */}
      <div style={{ paddingLeft: 10, marginBottom: 12 }}>
        <div
          style={{
            fontSize: 10,
            fontWeight: 700,
            color: '#9ca3af',
            textTransform: 'uppercase',
            letterSpacing: '0.06em',
            marginBottom: 7,
          }}
        >
          Tallas y cantidades
        </div>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
          {item.tallas.map(({ talla, cantidad }) => (
            <div
              key={`${item.id}-${talla}`}
              style={{
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                background: isRecibido ? 'rgba(22,163,74,0.07)' : `${accent}0d`,
                border: isRecibido ? '1.5px solid #bbf7d0' : `1.5px solid ${accent}30`,
                borderRadius: 10,
                padding: '5px 12px',
                minWidth: 52,
              }}
            >
              <span style={{ fontSize: 13, fontWeight: 800, color: isRecibido ? '#16a34a' : accent }}>{talla}</span>
              <span style={{ fontSize: 11, fontWeight: 600, color: '#374151' }}>{cantidad} uds</span>
            </div>
          ))}
          {/* Total */}
          <div
            style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              background: '#f3f4f6',
              border: '1.5px solid #e5e7eb',
              borderRadius: 10,
              padding: '5px 12px',
              minWidth: 52,
            }}
          >
            <span style={{ fontSize: 10, fontWeight: 700, color: '#9ca3af', textTransform: 'uppercase', letterSpacing: '0.04em' }}>Total</span>
            <span style={{ fontSize: 13, fontWeight: 800, color: '#111827' }}>{totalUds}</span>
          </div>
        </div>
      </div>

      {/* Pedido / Recibo */}
      <div style={{ display: 'flex', gap: 8, paddingLeft: 10, marginBottom: isRecibido ? 10 : 14 }}>
        <div
          style={{
            background: '#faf5ff',
            borderRadius: 8,
            padding: '5px 10px',
            display: 'flex',
            flexDirection: 'column',
            flex: 1,
            border: '1px solid #e9d5ff',
          }}
        >
          <span style={{ fontSize: 10, color: '#7c3aed', fontWeight: 600, letterSpacing: '0.05em', textTransform: 'uppercase' }}>
            N° Pedido
          </span>
          <span style={{ fontSize: 12, fontWeight: 700, color: '#7c3aed', marginTop: 1 }}>{item.pedido}</span>
        </div>
        <div
          style={{
            background: '#f9fafb',
            borderRadius: 8,
            padding: '5px 10px',
            display: 'flex',
            flexDirection: 'column',
            flex: 1,
            border: '1px solid #f0f0f0',
          }}
        >
          <span style={{ fontSize: 10, color: '#6b7280', fontWeight: 600, letterSpacing: '0.05em', textTransform: 'uppercase' }}>
            N° Recibo
          </span>
          <span style={{ fontSize: 12, fontWeight: 700, color: '#374151', marginTop: 1 }}>{item.recibo}</span>
        </div>
      </div>

      {/* Fecha/hora recepción */}
      {isRecibido && item.fechaHora && (
        <div
          style={{
            paddingLeft: 10,
            marginBottom: 0,
            display: 'flex',
            alignItems: 'center',
            gap: 6,
            fontSize: 11,
            color: '#16a34a',
            fontWeight: 600,
          }}
        >
          <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
            <circle cx="6.5" cy="6.5" r="5.5" stroke="#16a34a" strokeWidth="1.5" />
            <path d="M6.5 3.5v3l2 1.5" stroke="#16a34a" strokeWidth="1.5" strokeLinecap="round" />
          </svg>
          Recibido (Recepcion-Despacho): {formatFechaHora(item.fechaHora)}
        </div>
      )}

      {/* Action buttons */}
      <div style={{ display: 'flex', gap: 8 }}>
        {!isRecibido && (
          <button
            onClick={() => onConfirm(item.id)}
            style={{
              flex: 1,
              padding: '12px 0',
              borderRadius: 12,
              background: accent,
              color: '#fff',
              border: 'none',
              fontSize: 14,
              fontWeight: 700,
              cursor: 'pointer',
              letterSpacing: '0.03em',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: 8,
              transition: 'opacity 0.15s, transform 0.1s',
              WebkitTapHighlightColor: 'transparent',
            }}
            onMouseDown={(e) => (e.currentTarget.style.transform = 'scale(0.98)')}
            onMouseUp={(e) => (e.currentTarget.style.transform = 'scale(1)')}
            onTouchStart={(e) => (e.currentTarget.style.opacity = '0.85')}
            onTouchEnd={(e) => (e.currentTarget.style.opacity = '1')}
          >
            <IconCheck /> Confirmar
          </button>
        )}
        <button
          onClick={() => onNovedades(item.id, item.pedido, item.recibo, item.prenda)}
          style={{
            flex: isRecibido ? 1 : 0.4,
            padding: '12px 0',
            borderRadius: 12,
            background: '#f3f4f6',
            color: '#374151',
            border: '1.5px solid #e5e7eb',
            fontSize: 14,
            fontWeight: 700,
            cursor: 'pointer',
            letterSpacing: '0.03em',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: 8,
            transition: 'opacity 0.15s, transform 0.1s',
            WebkitTapHighlightColor: 'transparent',
          }}
          onMouseDown={(e) => (e.currentTarget.style.transform = 'scale(0.98)')}
          onMouseUp={(e) => (e.currentTarget.style.transform = 'scale(1)')}
          onTouchStart={(e) => (e.currentTarget.style.opacity = '0.85')}
          onTouchEnd={(e) => (e.currentTarget.style.opacity = '1')}
          title="Ver y agregar novedades"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
            <path d="M8 1C4.13 1 1 4.13 1 8s3.13 7 7 7 7-3.13 7-7-3.13-7-7-7z" stroke="currentColor" strokeWidth="1.5" fill="none" />
            <path d="M8 5v6M5 8h6" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
          </svg>
        </button>
      </div>
    </div>
  );
}

// Summary view
function SummaryView({ items, accent }) {
  const total = items.length;
  const recibidos = items.filter((i) => i.status === 'recibido').length;
  const pendientes = total - recibidos;
  const pct = total ? Math.round((recibidos / total) * 100) : 0;
  const totalPrendas = items.reduce((s, i) => s + i.tallas.reduce((a, t) => a + t.cantidad, 0), 0);
  const recibidasPrendas = items
    .filter((i) => i.status === 'recibido')
    .reduce((s, i) => s + i.tallas.reduce((a, t) => a + t.cantidad, 0), 0);

  const byClient = {};
  items.forEach((item) => {
    if (!byClient[item.cliente]) byClient[item.cliente] = { total: 0, recibido: 0 };
    byClient[item.cliente].total++;
    if (item.status === 'recibido') byClient[item.cliente].recibido++;
  });

  return (
    <div style={{ padding: '0 16px 80px' }}>
      <div style={{ background: accent, borderRadius: 20, padding: '20px', marginBottom: 14, color: '#fff' }}>
        <div style={{ fontSize: 13, fontWeight: 600, opacity: 0.8, marginBottom: 4 }}>Progreso del turno</div>
        <div style={{ fontSize: 36, fontWeight: 800, lineHeight: 1 }}>{pct}%</div>
        <div style={{ fontSize: 13, opacity: 0.75, marginBottom: 14 }}>
          {recibidos} de {total} órdenes confirmadas
        </div>
        <div style={{ height: 8, background: 'rgba(255,255,255,0.25)', borderRadius: 999 }}>
          <div
            style={{
              height: '100%',
              width: `${pct}%`,
              background: '#fff',
              borderRadius: 999,
              transition: 'width 0.6s ease',
            }}
          />
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10, marginBottom: 14 }}>
        {[
          { label: 'Pendientes', value: pendientes, color: '#f59e0b', bg: '#fffbeb', border: '#fde68a' },
          { label: 'Recibidos', value: recibidos, color: '#16a34a', bg: '#f0fdf4', border: '#bbf7d0' },
          { label: 'Total prendas', value: totalPrendas, color: '#6366f1', bg: '#eef2ff', border: '#c7d2fe' },
          { label: 'Prendas recibidas', value: recibidasPrendas, color: '#0891b2', bg: '#ecfeff', border: '#a5f3fc' },
        ].map((s) => (
          <div
            key={s.label}
            style={{
              background: s.bg,
              border: `1.5px solid ${s.border}`,
              borderRadius: 14,
              padding: '14px 14px',
            }}
          >
            <div
              style={{
                fontSize: 11,
                fontWeight: 600,
                color: s.color,
                textTransform: 'uppercase',
                letterSpacing: '0.05em',
                marginBottom: 4,
              }}
            >
              {s.label}
            </div>
            <div style={{ fontSize: 28, fontWeight: 800, color: s.color }}>{s.value}</div>
          </div>
        ))}
      </div>

      <div style={{ background: '#fff', borderRadius: 16, overflow: 'hidden', border: '1.5px solid #f0f0f0' }}>
        <div
          style={{
            padding: '14px 16px 10px',
            fontSize: 12,
            fontWeight: 700,
            color: '#6b7280',
            textTransform: 'uppercase',
            letterSpacing: '0.06em',
            borderBottom: '1px solid #f3f4f6',
          }}
        >
          Por cliente
        </div>
        {Object.entries(byClient).map(([cliente, data], i, arr) => (
          <div
            key={cliente}
            style={{
              display: 'flex',
              alignItems: 'center',
              padding: '12px 16px',
              borderBottom: i < arr.length - 1 ? '1px solid #f3f4f6' : 'none',
            }}
          >
            <div style={{ flex: 1 }}>
              <div style={{ fontSize: 14, fontWeight: 600, color: '#111827' }}>{cliente}</div>
              <div style={{ fontSize: 12, color: '#6b7280' }}>
                {data.recibido}/{data.total} órdenes
              </div>
            </div>
            <div style={{ width: 80, height: 6, background: '#f3f4f6', borderRadius: 999, overflow: 'hidden' }}>
              <div
                style={{
                  height: '100%',
                  width: `${(data.recibido / data.total) * 100}%`,
                  background: data.recibido === data.total ? '#16a34a' : accent,
                  borderRadius: 999,
                  transition: 'width 0.5s',
                }}
              />
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

// Main App
export default function RecepcionPrendas({ initialData = [], pagination = null, counts = null }) {
  const [items, setItems] = useState(initialData);
  const [filter, setFilter] = useState('todos');
  const [search, setSearch] = useState('');
  const [animatingId, setAnimatingId] = useState(null);
  const [toast, setToast] = useState(null);
  const [currentPage, setCurrentPage] = useState(pagination?.current_page || 1);
  const [paginationData, setPaginationData] = useState(pagination);
  const [pagesByFilter, setPagesByFilter] = useState({
    todos: pagination?.current_page || 1,
    pendientes: 1,
    recibidos: 1,
  });
  const [isLoading, setIsLoading] = useState(false);
  const [itemCounts, setItemCounts] = useState(counts || { total: 0, pendientes: 0, recibidos: 0 });
  const [showDateModal, setShowDateModal] = useState(false);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const hasDateFilter = dateFrom || dateTo;
  const [confirmDialogOpen, setConfirmDialogOpen] = useState(false);
  const [pendingConfirmId, setPendingConfirmId] = useState(null);
  const [showNovedadesModal, setShowNovedadesModal] = useState(false);
  const [selectedItemForNovedades, setSelectedItemForNovedades] = useState(null);
  const [novedades, setNovedades] = useState([]);
  const [novedadesLoading, setNovedadesLoading] = useState(false);
  const [novedadForm, setNovedadForm] = useState({ novedad_texto: '' });
  const [submittingNovedad, setSubmittingNovedad] = useState(false);
  const [editingNovedadId, setEditingNovedadId] = useState(null);
  const [deletingNovedadId, setDeletingNovedadId] = useState(null);
  const [confirmDeleteModal, setConfirmDeleteModal] = useState(null);
  const toastTimer = useRef(null);

  const accent = '#2563eb';
  const dark = false;

  const confirm = (id) => {
    setPendingConfirmId(id);
    setConfirmDialogOpen(true);
  };

  const openNovedadesModal = (id, pedido, recibo, prenda) => {
    setSelectedItemForNovedades({ id, pedido, recibo, prenda });
    setNovedadForm({ novedad_texto: '' });
    setShowNovedadesModal(true);
    loadNovedades(id);
  };

  const loadNovedades = async (itemId) => {
    setNovedadesLoading(true);
    try {
      const response = await fetch(`/api/recepcion-despacho/${itemId}/novedades`, {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          Accept: 'application/json',
        },
      });

      if (!response.ok) throw new Error('Error cargando novedades');
      const data = await response.json();
      setNovedades(data.data || []);
    } catch (error) {
      console.error('Error loading novedades:', error);
      setToast('❌ Error al cargar novedades');
      clearTimeout(toastTimer.current);
      toastTimer.current = setTimeout(() => setToast(null), 2500);
    } finally {
      setNovedadesLoading(false);
    }
  };

  const submitNovedad = async () => {
    if (!novedadForm.novedad_texto.trim()) {
      setToast('⚠ Escribe una novedad');
      clearTimeout(toastTimer.current);
      toastTimer.current = setTimeout(() => setToast(null), 2500);
      return;
    }

    setSubmittingNovedad(true);
    try {
      const url = editingNovedadId
        ? `/api/recepcion-despacho/novedades/${editingNovedadId}`
        : `/api/recepcion-despacho/${selectedItemForNovedades.id}/novedades`;

      const method = editingNovedadId ? 'PUT' : 'POST';

      const response = await fetch(url, {
        method,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          ...novedadForm,
          tipo_novedad: 'observacion',
        }),
      });

      if (!response.ok) throw new Error('Error guardando novedad');

      setNovedadForm({ novedad_texto: '' });
      setEditingNovedadId(null);
      setToast(editingNovedadId ? '✓ Novedad actualizada' : '✓ Novedad guardada');
      clearTimeout(toastTimer.current);
      toastTimer.current = setTimeout(() => setToast(null), 2500);

      await loadNovedades(selectedItemForNovedades.id);
    } catch (error) {
      console.error('Error submitting novedad:', error);
      setToast('❌ Error al guardar novedad');
      clearTimeout(toastTimer.current);
      toastTimer.current = setTimeout(() => setToast(null), 2500);
    } finally {
      setSubmittingNovedad(false);
    }
  };

  const deleteNovedad = async (novedadId) => {
    setConfirmDeleteModal(novedadId);
  };

  const confirmDelete = async () => {
    if (!confirmDeleteModal) return;

    setDeletingNovedadId(confirmDeleteModal);
    try {
      const response = await fetch(`/api/recepcion-despacho/novedades/${confirmDeleteModal}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          Accept: 'application/json',
        },
      });

      if (!response.ok) throw new Error('Error eliminando novedad');

      setToast('✓ Novedad eliminada');
      clearTimeout(toastTimer.current);
      toastTimer.current = setTimeout(() => setToast(null), 2500);

      await loadNovedades(selectedItemForNovedades.id);
    } catch (error) {
      console.error('Error deleting novedad:', error);
      setToast('❌ Error al eliminar novedad');
      clearTimeout(toastTimer.current);
      toastTimer.current = setTimeout(() => setToast(null), 2500);
    } finally {
      setDeletingNovedadId(null);
      setConfirmDeleteModal(null);
    }
  };

  const editNovedad = (nov) => {
    setEditingNovedadId(nov.id);
    setNovedadForm({ novedad_texto: nov.novedad_texto });
    window.scrollTo({ top: document.querySelector('textarea')?.offsetTop - 100, behavior: 'smooth' });
  };

  const confirmReceipt = async () => {
    if (!pendingConfirmId) return;

    setAnimatingId(pendingConfirmId);
    const item = items.find((i) => i.id === pendingConfirmId);
    const now = new Date().toISOString();

    try {
      const response = await fetch(`/api/recepcion-despacho/${pendingConfirmId}/confirmar`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          status: 'recibido',
          fechaHora: now,
          tallas: item.tallas,
        }),
      });

      if (!response.ok) {
        throw new Error('Error al guardar la confirmación');
      }

      setTimeout(() => {
        setItems((prev) =>
          prev.map((i) => (i.id === pendingConfirmId ? { ...i, status: 'recibido', fechaHora: now } : i))
        );
        setAnimatingId(null);
        setToast(`✓ ${item.prenda} recibida`);
        clearTimeout(toastTimer.current);
        toastTimer.current = setTimeout(() => setToast(null), 2500);

        setConfirmDialogOpen(false);
        setPendingConfirmId(null);
      }, 400);
    } catch (error) {
      console.error('Error confirming receipt:', error);
      setAnimatingId(null);
      setToast('❌ Error al guardar la confirmación');
      clearTimeout(toastTimer.current);
      toastTimer.current = setTimeout(() => setToast(null), 2500);
    }
  };

  const clearDateFilter = async () => {
    setDateFrom('');
    setDateTo('');
    setPagesByFilter({
      todos: 1,
      pendientes: 1,
      recibidos: 1,
    });
    setIsLoading(true);
    try {
      const params = new URLSearchParams();
      params.append('page', 1);
      params.append('status', filter);

      const response = await fetch(`/api/recepcion-despacho/items?${params.toString()}`, {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          Accept: 'application/json',
        },
      });

      if (!response.ok) throw new Error('Error cargando página');
      const data = await response.json();

      setItems(data.data || []);
      setPaginationData(data.pagination);
      if (data.counts) {
        setItemCounts(data.counts);
      }
      setCurrentPage(1);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
      console.error('Error loading page:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const loadPage = async (page, targetFilter = filter) => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams();
      params.append('page', page);
      params.append('status', targetFilter);
      if (dateFrom) params.append('date_from', dateFrom);
      if (dateTo) params.append('date_to', dateTo);

      const response = await fetch(`/api/recepcion-despacho/items?${params.toString()}`, {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          Accept: 'application/json',
        },
      });

      if (!response.ok) throw new Error('Error cargando página');
      const data = await response.json();

      setItems(data.data || []);
      setPaginationData(data.pagination);
      if (data.counts) {
        setItemCounts(data.counts);
      }
      setCurrentPage(page);
      setPagesByFilter((prev) => ({ ...prev, [targetFilter]: page }));
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
      console.error('Error loading page:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const filtered = items.filter((i) => {
    if (filter === 'pendientes' && i.status !== 'pendiente') return false;
    if (filter === 'recibidos' && i.status !== 'recibido') return false;
    if (search.trim()) {
      const q = search.toLowerCase();
      return (
        i.cliente.toLowerCase().includes(q) ||
        i.prenda.toLowerCase().includes(q) ||
        i.pedido.toLowerCase().includes(q) ||
        i.recibo.toLowerCase().includes(q)
      );
    }
    return true;
  });

  const bgApp = dark ? '#0f1923' : '#f4f7fb';
  const bgHeader = dark ? '#141f2c' : '#fff';
  const textPrimary = dark ? '#f9fafb' : '#111827';
  const textSecondary = dark ? '#9ca3af' : '#6b7280';

  const filterTabs = [
    { key: 'todos', label: `Todos (${itemCounts.total})` },
    { key: 'pendientes', label: `Pendientes (${itemCounts.pendientes})` },
    { key: 'recibidos', label: `Recibidos (${itemCounts.recibidos})` },
  ];

  return (
    <div
      style={{
        height: '100%',
        display: 'flex',
        flexDirection: 'column',
        background: bgApp,
        overflowY: 'auto',
        fontFamily: "'Inter', -apple-system, system-ui, sans-serif",
      }}
    >
      {/* Header */}
      <div
        style={{
          background: bgHeader,
          padding: '16px',
          borderBottom: dark ? '1px solid rgba(255,255,255,0.07)' : '1px solid #eef0f3',
          boxShadow: '0 1px 8px rgba(0,0,0,0.04)',
          position: 'sticky',
          top: 0,
          zIndex: 10,
        }}
      >
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 14 }}>
          <div>
            <div
              style={{
                fontSize: 11,
                fontWeight: 700,
                letterSpacing: '0.08em',
                textTransform: 'uppercase',
                color: accent,
                marginBottom: 2,
              }}
            >
              Área de Despacho
            </div>
            <div style={{ fontSize: 24, fontWeight: 800, color: textPrimary, lineHeight: 1.1 }}>
              Recepción
            </div>
          </div>
          <div style={{ display: 'flex', gap: 8 }}>
            <button
              onClick={() => setShowDateModal(true)}
              style={{
                width: 38,
                height: 38,
                borderRadius: 12,
                background: dark ? 'rgba(255,255,255,0.08)' : '#f3f4f6',
                border: 'none',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                color: textSecondary,
              }}
            >
              <IconFilter />
            </button>
          </div>
        </div>

        {/* Search bar */}
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: 10,
            background: dark ? 'rgba(255,255,255,0.07)' : '#f3f4f6',
            borderRadius: 12,
            padding: '9px 14px',
            marginBottom: 12,
          }}
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" style={{ flexShrink: 0 }}>
            <circle cx="7" cy="7" r="5.5" stroke={textSecondary} strokeWidth="1.8" />
            <path d="M11 11l3 3" stroke={textSecondary} strokeWidth="1.8" strokeLinecap="round" />
          </svg>
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Buscar cliente, prenda o pedido…"
            style={{
              flex: 1,
              border: 'none',
              background: 'transparent',
              fontSize: 14,
              color: textPrimary,
              outline: 'none',
              fontFamily: "'Inter', sans-serif",
            }}
          />
          {search && (
            <button
              onClick={() => setSearch('')}
              style={{
                border: 'none',
                background: 'none',
                cursor: 'pointer',
                padding: 0,
                display: 'flex',
                alignItems: 'center',
              }}
            >
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="7" fill={textSecondary} fillOpacity="0.25" />
                <path
                  d="M5.5 5.5l5 5M10.5 5.5l-5 5"
                  stroke={textSecondary}
                  strokeWidth="1.6"
                  strokeLinecap="round"
                />
              </svg>
            </button>
          )}
        </div>

        {/* Filter tabs */}
        <div
          style={{
            display: 'flex',
            gap: 0,
            marginBottom: 0,
            borderBottom: dark ? '1px solid rgba(255,255,255,0.07)' : '1px solid #eef0f3',
          }}
        >
          {filterTabs.map((tab) => {
            const isActive = tab.key === filter;
            return (
              <button
                key={tab.key}
                onClick={() => {
                  setFilter(tab.key);
                  const pageForTab = pagesByFilter[tab.key] || 1;
                  loadPage(pageForTab, tab.key);
                }}
                style={{
                  flex: 1,
                  padding: '10px 4px',
                  border: 'none',
                  cursor: 'pointer',
                  background: 'transparent',
                  fontSize: 12,
                  fontWeight: isActive ? 700 : 500,
                  color: isActive ? accent : textSecondary,
                  borderBottom: isActive ? `2.5px solid ${accent}` : '2.5px solid transparent',
                  transition: 'all 0.2s',
                  letterSpacing: '0.01em',
                }}
              >
                {tab.label}
              </button>
            );
          })}
        </div>
      </div>

      {/* Body */}
      <div style={{ flex: 1, overflowY: 'auto', padding: '14px 14px 14px' }}>
        {filtered.length === 0 ? (
          <div style={{ textAlign: 'center', paddingTop: 60, color: textSecondary }}>
            <div style={{ fontSize: 40, marginBottom: 12 }}>📦</div>
            <div style={{ fontSize: 16, fontWeight: 600 }}>Sin prendas {filter}</div>
          </div>
        ) : (
          <>
            {filtered.map((item) => (
              <PrendaCard
                key={item.id}
                item={item}
                onConfirm={confirm}
                onNovedades={openNovedadesModal}
                accent={accent}
                animatingId={animatingId}
              />
            ))}

            {/* Pagination */}
            {paginationData && paginationData.last_page > 1 && (
              <div
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  padding: '12px 16px',
                  background: bgHeader,
                  borderTop: dark ? '1px solid rgba(255,255,255,0.07)' : '1px solid #eef0f3',
                  borderRadius: 12,
                  gap: 6,
                  marginTop: 8,
                  marginBottom: 80,
                }}
              >
                <button
                  onClick={() => loadPage(1, filter)}
                  disabled={currentPage === 1}
                  style={{
                    padding: '8px 10px',
                    borderRadius: 8,
                    border: 'none',
                    background: currentPage === 1 ? '#e5e7eb' : accent,
                    color: currentPage === 1 ? '#9ca3af' : '#fff',
                    cursor: currentPage === 1 ? 'not-allowed' : 'pointer',
                    fontWeight: 600,
                    fontSize: 12,
                    minWidth: 40,
                  }}
                >
                  ⟨⟨
                </button>

                <button
                  onClick={() => loadPage(currentPage - 1, filter)}
                  disabled={currentPage === 1}
                  style={{
                    flex: 1,
                    padding: '8px',
                    borderRadius: 8,
                    border: 'none',
                    background: currentPage === 1 ? '#e5e7eb' : accent,
                    color: currentPage === 1 ? '#9ca3af' : '#fff',
                    cursor: currentPage === 1 ? 'not-allowed' : 'pointer',
                    fontWeight: 600,
                    fontSize: 12,
                  }}
                >
                  ← Anterior
                </button>

                <div style={{ fontSize: 12, fontWeight: 600, color: textSecondary, minWidth: 70, textAlign: 'center' }}>
                  {currentPage} / {paginationData.last_page}
                </div>

                <button
                  onClick={() => loadPage(currentPage + 1, filter)}
                  disabled={currentPage === paginationData.last_page}
                  style={{
                    flex: 1,
                    padding: '8px',
                    borderRadius: 8,
                    border: 'none',
                    background: currentPage === paginationData.last_page ? '#e5e7eb' : accent,
                    color: currentPage === paginationData.last_page ? '#9ca3af' : '#fff',
                    cursor: currentPage === paginationData.last_page ? 'not-allowed' : 'pointer',
                    fontWeight: 600,
                    fontSize: 12,
                  }}
                >
                  Siguiente →
                </button>

                <button
                  onClick={() => loadPage(paginationData.last_page, filter)}
                  disabled={currentPage === paginationData.last_page}
                  style={{
                    padding: '8px 10px',
                    borderRadius: 8,
                    border: 'none',
                    background: currentPage === paginationData.last_page ? '#e5e7eb' : accent,
                    color: currentPage === paginationData.last_page ? '#9ca3af' : '#fff',
                    cursor: currentPage === paginationData.last_page ? 'not-allowed' : 'pointer',
                    fontWeight: 600,
                    fontSize: 12,
                    minWidth: 40,
                  }}
                >
                  ⟩⟩
                </button>
              </div>
            )}
          </>
        )}
      </div>

      {/* Confirmation dialog */}
      {confirmDialogOpen && (
        <div
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0,0,0,0.4)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 50,
            backdropFilter: 'blur(2px)',
          }}
          onClick={() => {
            setConfirmDialogOpen(false);
            setPendingConfirmId(null);
          }}
        >
          <div
            style={{
              background: bgHeader,
              borderRadius: 20,
              padding: '24px',
              maxWidth: 320,
              boxShadow: '0 20px 60px rgba(0,0,0,0.3)',
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <div style={{ fontSize: 18, fontWeight: 700, color: '#111827', marginBottom: 12 }}>
              ¿Estás seguro?
            </div>

            <div style={{ fontSize: 14, color: '#6b7280', marginBottom: 20, lineHeight: 1.5 }}>
              ¿Confirmas que recibiste {items.find((i) => i.id === pendingConfirmId)?.prenda}?
            </div>

            <div style={{ display: 'flex', gap: 10 }}>
              <button
                onClick={() => {
                  setConfirmDialogOpen(false);
                  setPendingConfirmId(null);
                }}
                style={{
                  flex: 1,
                  padding: '12px',
                  borderRadius: 10,
                  border: '1.5px solid #e5e7eb',
                  background: '#fff',
                  color: '#6b7280',
                  cursor: 'pointer',
                  fontWeight: 600,
                  fontSize: 14,
                }}
              >
                Cancelar
              </button>
              <button
                onClick={confirmReceipt}
                style={{
                  flex: 1,
                  padding: '12px',
                  borderRadius: 10,
                  border: 'none',
                  background: accent,
                  color: '#fff',
                  cursor: 'pointer',
                  fontWeight: 600,
                  fontSize: 14,
                }}
              >
                Confirmar
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Date filter modal */}
      {showDateModal && (
        <div
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0,0,0,0.4)',
            display: 'flex',
            alignItems: 'flex-end',
            zIndex: 50,
            backdropFilter: 'blur(2px)',
          }}
          onClick={() => setShowDateModal(false)}
        >
          <div
            style={{
              background: bgHeader,
              borderTopLeftRadius: 20,
              borderTopRightRadius: 20,
              padding: '24px 16px 32px',
              width: '100%',
              boxShadow: '0 -4px 20px rgba(0,0,0,0.15)',
              animation: 'slideUp 0.3s ease',
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <div style={{ fontSize: 16, fontWeight: 700, color: '#111827', marginBottom: 16 }}>
              Filtrar por fecha de entrega
            </div>

            <div style={{ display: 'flex', gap: 12, marginBottom: 20 }}>
              <div style={{ flex: 1 }}>
                <label style={{ fontSize: 12, fontWeight: 600, color: '#6b7280', display: 'block', marginBottom: 6 }}>
                  Desde
                </label>
                <input
                  type="date"
                  value={dateFrom}
                  onChange={(e) => setDateFrom(e.target.value)}
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    borderRadius: 8,
                    border: '1.5px solid #e5e7eb',
                    fontSize: 14,
                    fontFamily: "'Inter', sans-serif",
                    boxSizing: 'border-box',
                  }}
                />
              </div>
              <div style={{ flex: 1 }}>
                <label style={{ fontSize: 12, fontWeight: 600, color: '#6b7280', display: 'block', marginBottom: 6 }}>
                  Hasta
                </label>
                <input
                  type="date"
                  value={dateTo}
                  onChange={(e) => setDateTo(e.target.value)}
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    borderRadius: 8,
                    border: '1.5px solid #e5e7eb',
                    fontSize: 14,
                    fontFamily: "'Inter', sans-serif",
                    boxSizing: 'border-box',
                  }}
                />
              </div>
            </div>

            <div style={{ display: 'flex', gap: 10 }}>
              <button
                onClick={() => setShowDateModal(false)}
                style={{
                  flex: 1,
                  padding: '12px',
                  borderRadius: 10,
                  border: '1.5px solid #e5e7eb',
                  background: '#fff',
                  color: '#6b7280',
                  cursor: 'pointer',
                  fontWeight: 600,
                  fontSize: 14,
                }}
              >
                Cancelar
              </button>
              <button
                onClick={() => {
                  setPagesByFilter({
                    todos: 1,
                    pendientes: 1,
                    recibidos: 1,
                  });
                  loadPage(1, filter);
                  setShowDateModal(false);
                }}
                style={{
                  flex: 1,
                  padding: '12px',
                  borderRadius: 10,
                  border: 'none',
                  background: accent,
                  color: '#fff',
                  cursor: 'pointer',
                  fontWeight: 600,
                  fontSize: 14,
                }}
              >
                Aplicar filtro
              </button>
            </div>
          </div>
          <style>
            {`
              @keyframes slideUp {
                from { transform: translateY(100%); }
                to { transform: translateY(0); }
              }
            `}
          </style>
        </div>
      )}

      {/* Loading overlay */}
      {isLoading && (
        <div
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0,0,0,0.4)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 50,
            backdropFilter: 'blur(2px)',
          }}
        >
          <div
            style={{
              background: bgHeader,
              borderRadius: 20,
              padding: '32px',
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: 16,
              boxShadow: '0 20px 60px rgba(0,0,0,0.3)',
            }}
          >
            <div
              style={{
                width: 48,
                height: 48,
                borderRadius: '50%',
                border: `3px solid ${accent}33`,
                borderTop: `3px solid ${accent}`,
                animation: 'spin 1s linear infinite',
              }}
            />
            <div style={{ fontSize: 14, fontWeight: 600, color: textPrimary }}>Cargando...</div>
          </div>
          <style>
            {`
              @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
              }
            `}
          </style>
        </div>
      )}

      {/* Novedades Modal */}
      {showNovedadesModal && selectedItemForNovedades && (
        <div
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0,0,0,0.4)',
            display: 'flex',
            alignItems: 'flex-end',
            zIndex: 50,
            backdropFilter: 'blur(2px)',
          }}
          onClick={() => setShowNovedadesModal(false)}
        >
          <div
            style={{
              background: bgHeader,
              borderTopLeftRadius: 20,
              borderTopRightRadius: 20,
              padding: '24px 16px 32px',
              width: '100%',
              maxHeight: '80vh',
              display: 'flex',
              flexDirection: 'column',
              boxShadow: '0 -4px 20px rgba(0,0,0,0.15)',
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
              <div>
                <div style={{ fontSize: 12, fontWeight: 600, color: textSecondary, textTransform: 'uppercase' }}>
                  {selectedItemForNovedades.prenda}
                </div>
                <div style={{ fontSize: 16, fontWeight: 700, color: textPrimary }}>
                  Novedades
                </div>
              </div>
              <button
                onClick={() => setShowNovedadesModal(false)}
                style={{
                  background: 'none',
                  border: 'none',
                  fontSize: 24,
                  cursor: 'pointer',
                  color: textSecondary,
                  padding: 0,
                  width: 32,
                  height: 32,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                ✕
              </button>
            </div>

            {/* Novedades List */}
            <div style={{ flex: 1, overflowY: 'auto', marginBottom: 16, minHeight: 0 }}>
              {novedadesLoading ? (
                <div style={{ textAlign: 'center', padding: '20px', color: textSecondary }}>
                  <div style={{ animation: 'spin 1s linear infinite', display: 'inline-block' }}>⏳</div>
                  <div style={{ marginTop: 8 }}>Cargando novedades...</div>
                </div>
              ) : novedades.length === 0 ? (
                <div style={{ textAlign: 'center', padding: '20px', color: textSecondary }}>
                  <div style={{ fontSize: 20, marginBottom: 8 }}>📝</div>
                  <div>Sin novedades aún</div>
                </div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                  {novedades.map((nov) => (
                    <div
                      key={nov.id}
                      style={{
                        background: editingNovedadId === nov.id ? accent + '10' : (dark ? '#1f2937' : '#f9fafb'),
                        border: `1px solid ${editingNovedadId === nov.id ? accent : (dark ? '#374151' : '#e5e7eb')}`,
                        borderRadius: 12,
                        padding: '12px',
                      }}
                    >
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 6 }}>
                        <div>
                          <div
                            style={{
                              display: 'inline-block',
                              padding: '2px 8px',
                              borderRadius: 4,
                              fontSize: 10,
                              fontWeight: 700,
                              textTransform: 'uppercase',
                              background: '#bfdbfe',
                              color: '#1e40af',
                              marginBottom: 4,
                            }}
                          >
                            Observación
                          </div>
                        </div>
                        {nov.es_mio && (
                          <div style={{ display: 'flex', gap: 6, alignItems: 'center' }}>
                            <button
                              onClick={() => editNovedad(nov)}
                              style={{
                                background: 'none',
                                border: 'none',
                                cursor: 'pointer',
                                color: accent,
                                fontSize: 12,
                                fontWeight: 600,
                                padding: 0,
                              }}
                              title="Editar"
                            >
                              ✎
                            </button>
                            <button
                              onClick={() => deleteNovedad(nov.id)}
                              disabled={deletingNovedadId === nov.id}
                              style={{
                                background: 'none',
                                border: 'none',
                                cursor: deletingNovedadId === nov.id ? 'not-allowed' : 'pointer',
                                color: '#ef4444',
                                fontSize: 12,
                                fontWeight: 600,
                                padding: 0,
                                opacity: deletingNovedadId === nov.id ? 0.5 : 1,
                              }}
                              title="Eliminar"
                            >
                              ✕
                            </button>
                          </div>
                        )}
                      </div>
                      <div style={{ fontSize: 13, color: textPrimary, marginBottom: 6, lineHeight: 1.4 }}>
                        {nov.novedad_texto}
                      </div>
                      <div style={{ fontSize: 11, color: textSecondary }}>
                        {nov.creado_por_nombre} • {nov.creado_en}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Add/Edit Novedad Form */}
            <div style={{ borderTop: dark ? '1px solid #374151' : '1px solid #e5e7eb', paddingTop: 16 }}>
              {editingNovedadId && (
                <div style={{ marginBottom: 12, padding: '8px 12px', background: accent + '10', borderRadius: 8, fontSize: 12, color: accent, fontWeight: 600 }}>
                  ✎ Editando novedad
                </div>
              )}
              <div style={{ marginBottom: 12 }}>
                <label style={{ fontSize: 12, fontWeight: 600, color: textSecondary, display: 'block', marginBottom: 6 }}>
                  Observación
                </label>
                <textarea
                  value={novedadForm.novedad_texto}
                  onChange={(e) => setNovedadForm({ ...novedadForm, novedad_texto: e.target.value })}
                  placeholder="Escribe la observación..."
                  style={{
                    width: '100%',
                    padding: '10px 12px',
                    borderRadius: 8,
                    border: `1.5px solid ${dark ? '#374151' : '#e5e7eb'}`,
                    background: bgHeader,
                    color: textPrimary,
                    fontSize: 14,
                    fontFamily: "'Inter', sans-serif",
                    boxSizing: 'border-box',
                    minHeight: 80,
                    resize: 'vertical',
                  }}
                />
              </div>

              <div style={{ display: 'flex', gap: 8 }}>
                <button
                  onClick={submitNovedad}
                  disabled={submittingNovedad}
                  style={{
                    flex: 1,
                    padding: '12px',
                    borderRadius: 10,
                    border: 'none',
                    background: submittingNovedad ? '#9ca3af' : accent,
                    color: '#fff',
                    cursor: submittingNovedad ? 'not-allowed' : 'pointer',
                    fontWeight: 600,
                    fontSize: 14,
                    transition: 'opacity 0.2s',
                  }}
                >
                  {submittingNovedad ? 'Guardando...' : editingNovedadId ? 'Actualizar' : 'Guardar Observación'}
                </button>
                {editingNovedadId && (
                  <button
                    onClick={() => {
                      setEditingNovedadId(null);
                      setNovedadForm({ novedad_texto: '' });
                    }}
                    style={{
                      padding: '12px 16px',
                      borderRadius: 10,
                      border: `1.5px solid ${dark ? '#374151' : '#e5e7eb'}`,
                      background: 'transparent',
                      color: textSecondary,
                      cursor: 'pointer',
                      fontWeight: 600,
                      fontSize: 14,
                    }}
                  >
                    Cancelar
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      {confirmDeleteModal && (
        <div
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0,0,0,0.4)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 60,
            backdropFilter: 'blur(2px)',
          }}
          onClick={() => setConfirmDeleteModal(null)}
        >
          <div
            style={{
              background: bgHeader,
              borderRadius: 20,
              padding: '24px',
              maxWidth: 320,
              boxShadow: '0 20px 60px rgba(0,0,0,0.3)',
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <div style={{ fontSize: 18, fontWeight: 700, color: '#111827', marginBottom: 12 }}>
              ¿Eliminar novedad?
            </div>

            <div style={{ fontSize: 14, color: '#6b7280', marginBottom: 20, lineHeight: 1.5 }}>
              Esta acción no se puede deshacer. ¿Estás seguro de que deseas eliminar esta observación?
            </div>

            <div style={{ display: 'flex', gap: 10 }}>
              <button
                onClick={() => setConfirmDeleteModal(null)}
                style={{
                  flex: 1,
                  padding: '12px',
                  borderRadius: 10,
                  border: '1.5px solid #e5e7eb',
                  background: '#fff',
                  color: '#6b7280',
                  cursor: 'pointer',
                  fontWeight: 600,
                  fontSize: 14,
                }}
              >
                Cancelar
              </button>
              <button
                onClick={confirmDelete}
                disabled={deletingNovedadId === confirmDeleteModal}
                style={{
                  flex: 1,
                  padding: '12px',
                  borderRadius: 10,
                  border: 'none',
                  background: '#ef4444',
                  color: '#fff',
                  cursor: deletingNovedadId === confirmDeleteModal ? 'not-allowed' : 'pointer',
                  fontWeight: 600,
                  fontSize: 14,
                  opacity: deletingNovedadId === confirmDeleteModal ? 0.7 : 1,
                }}
              >
                {deletingNovedadId === confirmDeleteModal ? 'Eliminando...' : 'Eliminar'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Clear Filter Button - Red Circle */}
      {hasDateFilter && (
        <button
          onClick={clearDateFilter}
          title="Limpiar filtro de fecha"
          style={{
            position: 'fixed',
            bottom: 100,
            right: 20,
            width: 56,
            height: 56,
            borderRadius: '50%',
            border: 'none',
            background: '#ef4444',
            color: '#fff',
            fontSize: 24,
            cursor: 'pointer',
            boxShadow: '0 4px 12px rgba(239, 68, 68, 0.3)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 40,
            transition: 'all 0.3s ease',
            animation: 'scaleIn 0.3s ease',
            WebkitTapHighlightColor: 'transparent',
          }}
          onMouseDown={(e) => (e.currentTarget.style.transform = 'scale(0.95)')}
          onMouseUp={(e) => (e.currentTarget.style.transform = 'scale(1)')}
          onTouchStart={(e) => (e.currentTarget.style.opacity = '0.85')}
          onTouchEnd={(e) => (e.currentTarget.style.opacity = '1')}
        >
          ✕
          <style>
            {`
              @keyframes scaleIn {
                from { transform: scale(0); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
              }
            `}
          </style>
        </button>
      )}

      {/* Toast */}
      <div
        style={{
          position: 'fixed',
          bottom: 90,
          left: 16,
          right: 16,
          background: '#111827',
          color: '#fff',
          borderRadius: 14,
          padding: '12px 18px',
          fontSize: 14,
          fontWeight: 600,
          textAlign: 'center',
          zIndex: 100,
          opacity: toast ? 1 : 0,
          transform: toast ? 'translateY(0)' : 'translateY(12px)',
          transition: 'all 0.3s cubic-bezier(0.4,0,0.2,1)',
          pointerEvents: 'none',
          boxShadow: '0 8px 24px rgba(0,0,0,0.25)',
        }}
      >
        {toast}
      </div>
    </div>
  );
}
