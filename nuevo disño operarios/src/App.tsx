/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from 'react';
import { 
  Search, 
  Scissors, 
  Sparkles, 
  Bell, 
  MoreHorizontal, 
  Undo2, 
  CheckCircle2, 
  PlusCircle, 
  ChevronRight,
  User,
  Calendar,
  Hash,
  Filter
} from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

interface Receipt {
  id: string;
  receiptNumber: string;
  clientName: string;
  description: string;
  status: 'COSTURA' | 'COMPLETADO' | 'REFLECTIVO';
  assignedTo: string;
  date: string;
  orderNumber: string;
}

const MOCK_DATA: Receipt[] = [
  {
    id: '1',
    receiptNumber: '2',
    clientName: 'EXTRACTORA SAN FERNANDO',
    description: 'CAMISA DRIL DOS PORTALAPICEROS EN EL BRAZO IZQUIERDO',
    status: 'COSTURA',
    assignedTo: 'MODULO 1',
    date: '26/02/2026',
    orderNumber: 'PEDIDO #16'
  },
  {
    id: '2',
    receiptNumber: '5',
    clientName: 'CONSTRUCTORA ALPHA',
    description: 'CHALECO REFLECTIVO CON LOGO BORDADO EN ESPALDA',
    status: 'REFLECTIVO',
    assignedTo: 'MODULO 2',
    date: '27/02/2026',
    orderNumber: 'PEDIDO #18'
  },
  {
    id: '3',
    receiptNumber: '8',
    clientName: 'AGROINDUSTRIA DEL NORTE',
    description: 'PANTALON CARGO REFORZADO COLOR AZUL NAVY',
    status: 'COMPLETADO',
    assignedTo: 'MODULO 1',
    date: '25/02/2026',
    orderNumber: 'PEDIDO #12'
  }
];

export default function App() {
  const [searchTerm, setSearchTerm] = useState('');
  const [activeTab, setActiveTab] = useState<'COSTURA' | 'REFLECTIVO'>('COSTURA');

  return (
    <div className="min-h-screen bg-slate-50 font-sans text-slate-900">
      {/* Header */}
      <header className="sticky top-0 z-30 bg-white border-b border-slate-200 px-4 py-3 md:px-8">
        <div className="max-w-7xl mx-auto flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-blue-200">
              T
            </div>
            <div className="hidden md:block">
              <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Vista-Costura</p>
              <p className="text-sm font-bold text-slate-800">tatiana</p>
            </div>
          </div>
          
          <div className="flex items-center gap-4">
            <button className="p-2 text-slate-500 hover:bg-slate-100 rounded-full transition-colors relative">
              <Bell size={20} />
              <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
            </button>
            <div className="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center md:hidden">
              <User size={18} className="text-slate-600" />
            </div>
          </div>
        </div>
      </header>

      <main className="max-w-4xl mx-auto px-4 py-6 md:py-10">
        {/* Search Section */}
        <div className="relative mb-8">
          <div className="absolute inset-y-0 left-4 flex items-center pointer-events-none">
            <Search size={18} className="text-slate-400" />
          </div>
          <input
            type="text"
            placeholder="Buscar por # Recibo o Cliente..."
            className="w-full bg-white border-none rounded-2xl py-4 pl-12 pr-4 shadow-sm focus:ring-2 focus:ring-blue-500 transition-all text-slate-700 placeholder:text-slate-400"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>

        {/* Title & Tabs */}
        <div className="mb-6">
          <div className="flex items-center justify-between mb-6">
            <div className="flex items-center gap-2">
              <Scissors size={20} className="text-slate-600" />
              <h1 className="text-lg font-bold text-slate-800 uppercase tracking-wide">Recibos de Costura</h1>
            </div>
            <span className="bg-slate-200 text-slate-600 text-xs font-bold px-2 py-1 rounded-md">
              {MOCK_DATA.length}
            </span>
          </div>

          <div className="flex gap-2 p-1 bg-slate-200/50 rounded-xl w-fit">
            <button 
              onClick={() => setActiveTab('COSTURA')}
              className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition-all ${
                activeTab === 'COSTURA' 
                ? 'bg-blue-600 text-white shadow-md' 
                : 'text-slate-500 hover:text-slate-700'
              }`}
            >
              <Scissors size={16} />
              COSTURA
            </button>
            <button 
              onClick={() => setActiveTab('REFLECTIVO')}
              className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition-all ${
                activeTab === 'REFLECTIVO' 
                ? 'bg-blue-600 text-white shadow-md' 
                : 'text-slate-500 hover:text-slate-700'
              }`}
            >
              <Sparkles size={16} />
              REFLECTIVO
            </button>
          </div>
        </div>

        {/* Receipts List */}
        <div className="space-y-4">
          {MOCK_DATA.map((receipt) => (
            <ReceiptCard key={receipt.id} receipt={receipt} />
          ))}
        </div>
      </main>
    </div>
  );
}

const ReceiptCard: React.FC<{ receipt: Receipt }> = ({ receipt }) => {
  const [isActionsOpen, setIsActionsOpen] = useState(false);

  return (
    <motion.div 
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      className="bg-white rounded-2xl border-l-4 border-blue-500 shadow-sm hover:shadow-md transition-shadow overflow-hidden"
    >
      <div className="p-5 md:p-6">
        {/* Top Row: ID and Status */}
        <div className="flex items-start justify-between mb-4">
          <div className="flex items-center gap-3">
            <div className="flex items-center gap-1 bg-slate-100 px-2 py-1 rounded-lg">
              <Hash size={14} className="text-slate-500" />
              <span className="text-lg font-black text-slate-800">{receipt.receiptNumber}</span>
            </div>
            <div className="flex flex-wrap gap-2">
              <span className="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded uppercase tracking-tighter">
                {receipt.status}
              </span>
              {receipt.status === 'COMPLETADO' && (
                <span className="text-[10px] font-bold bg-blue-100 text-blue-600 px-2 py-0.5 rounded uppercase tracking-tighter">
                  COMPLETADO COSTURA
                </span>
              )}
            </div>
          </div>
          <div className="flex items-center gap-2">
            <span className="hidden md:block text-[10px] font-bold text-slate-400 uppercase">Encar: <span className="text-slate-700">{receipt.assignedTo}</span></span>
            <div className="md:hidden">
              <button 
                onClick={() => setIsActionsOpen(!isActionsOpen)}
                className={`p-2 rounded-xl transition-colors ${isActionsOpen ? 'bg-blue-50 text-blue-600' : 'text-slate-400 hover:bg-slate-50'}`}
              >
                <MoreHorizontal size={20} />
              </button>
            </div>
          </div>
        </div>

        {/* Middle: Client and Description */}
        <div className="mb-6">
          <p className="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Cliente</p>
          <h3 className="text-base md:text-lg font-bold text-slate-800 leading-tight mb-2">
            {receipt.clientName}
          </h3>
          <p className="text-sm text-slate-500 font-medium leading-relaxed">
            {receipt.description}
          </p>
        </div>

        {/* Info Row */}
        <div className="flex flex-wrap items-center gap-y-3 gap-x-6 mb-6 pt-4 border-t border-slate-50">
          <div className="flex items-center gap-2">
            <div className="w-7 h-7 bg-slate-50 rounded-full flex items-center justify-center text-slate-400">
              <Calendar size={14} />
            </div>
            <div>
              <p className="text-[9px] font-bold text-slate-400 uppercase">Registro</p>
              <p className="text-xs font-bold text-slate-700">{receipt.date}</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-7 h-7 bg-slate-50 rounded-full flex items-center justify-center text-slate-400">
              <Filter size={14} />
            </div>
            <div>
              <p className="text-[9px] font-bold text-slate-400 uppercase">Recibo</p>
              <p className="text-xs font-bold text-slate-700">#{receipt.receiptNumber}</p>
            </div>
          </div>
          <div className="ml-auto">
            <span className="text-[10px] font-bold bg-slate-100 text-slate-500 px-2 py-1 rounded">
              {receipt.orderNumber}
            </span>
          </div>
        </div>

        {/* Desktop Actions */}
        <div className="hidden md:flex flex-wrap gap-2">
          <button className="flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition-all active:scale-95 shadow-sm shadow-amber-100">
            <Undo2 size={16} />
            DESHACER COSTURA
          </button>
          <button className="flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition-all active:scale-95 shadow-sm shadow-emerald-100">
            <CheckCircle2 size={16} />
            PASAR A C.C
          </button>
          <button className="flex items-center justify-center gap-2 bg-rose-500 hover:bg-rose-600 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition-all active:scale-95 shadow-sm shadow-rose-100">
            <PlusCircle size={16} />
            AGREGAR NOVEDAD
          </button>
          <button className="flex items-center justify-center gap-2 bg-white border-2 border-blue-500 text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-xl text-xs font-bold transition-all active:scale-95">
            <Scissors size={16} />
            COSTURA
          </button>
          <button className="flex items-center justify-center w-10 h-10 bg-slate-100 text-slate-400 hover:bg-slate-200 hover:text-slate-600 rounded-xl transition-all">
            <ChevronRight size={20} />
          </button>
        </div>

        {/* Mobile Actions Drawer/Menu */}
        <AnimatePresence>
          {isActionsOpen && (
            <motion.div 
              initial={{ height: 0, opacity: 0 }}
              animate={{ height: 'auto', opacity: 1 }}
              exit={{ height: 0, opacity: 0 }}
              className="md:hidden overflow-hidden"
            >
              <div className="grid grid-cols-1 gap-2 pt-4 border-t border-slate-100">
                <button className="flex items-center gap-3 bg-amber-500 text-white p-3 rounded-xl text-sm font-bold shadow-sm">
                  <Undo2 size={18} />
                  DESHACER COSTURA
                </button>
                <button className="flex items-center gap-3 bg-emerald-600 text-white p-3 rounded-xl text-sm font-bold shadow-sm">
                  <CheckCircle2 size={18} />
                  PASAR A C.C
                </button>
                <button className="flex items-center gap-3 bg-rose-500 text-white p-3 rounded-xl text-sm font-bold shadow-sm">
                  <PlusCircle size={18} />
                  AGREGAR NOVEDAD
                </button>
                <button className="flex items-center gap-3 bg-white border-2 border-blue-500 text-blue-600 p-3 rounded-xl text-sm font-bold">
                  <Scissors size={18} />
                  COSTURA
                </button>
              </div>
            </motion.div>
          )}
        </AnimatePresence>
        
        {/* Mobile Default Primary Action (if menu closed) */}
        {!isActionsOpen && (
          <div className="md:hidden flex gap-2">
            <button className="flex-1 flex items-center justify-center gap-2 bg-emerald-600 text-white p-3 rounded-xl text-sm font-bold shadow-sm">
              <CheckCircle2 size={18} />
              PASAR A C.C
            </button>
            <button 
              onClick={() => setIsActionsOpen(true)}
              className="px-4 bg-slate-100 text-slate-600 rounded-xl flex items-center justify-center"
            >
              <MoreHorizontal size={20} />
            </button>
          </div>
        )}
      </div>
    </motion.div>
  );
}
