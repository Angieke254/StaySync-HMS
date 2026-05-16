import { useState, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { addDays, format, differenceInDays, startOfDay, isToday, isWeekend } from 'date-fns';
import { ChevronLeft, ChevronRight, X, BedDouble, User, Calendar, Hash } from 'lucide-react';
import { mockTapeChartRooms } from '@/lib/mockData';
import { cn } from '@/lib/utils';

// ─── Constants ────────────────────────────────────────────────────────────────
const DAYS = 30;
const ROW_HEIGHT = 52;
const ROOM_COL_WIDTH = 140;
const DAY_WIDTH = 40;

const TYPE_COLORS: Record<string, { bg: string; text: string; border: string }> = {
  'Standard King': { bg: '#3b82f6', text: '#ffffff', border: '#2563eb' },
  'Deluxe King':   { bg: '#8b5cf6', text: '#ffffff', border: '#7c3aed' },
  'Deluxe Twin':   { bg: '#06b6d4', text: '#ffffff', border: '#0891b2' },
  'Suite':         { bg: '#f59e0b', text: '#ffffff', border: '#d97706' },
  'Penthouse':     { bg: '#ef4444', text: '#ffffff', border: '#dc2626' },
};

const STATUS_COLORS: Record<string, string> = {
  confirmed:   '#3b82f6',
  checked_in:  '#22c55e',
  checked_out: '#94a3b8',
  cancelled:   '#ef4444',
  pending:     '#f59e0b',
};

const ROOM_STATUS_BADGE: Record<string, { bg: string; label: string }> = {
  available:   { bg: '#22c55e', label: 'Available'   },
  occupied:    { bg: '#3b82f6', label: 'Occupied'    },
  dirty:       { bg: '#f59e0b', label: 'Dirty'       },
  cleaning:    { bg: '#a855f7', label: 'Cleaning'    },
  maintenance: { bg: '#ef4444', label: 'Maintenance' },
  blocked:     { bg: '#94a3b8', label: 'Blocked'     },
};

// ─── Types ────────────────────────────────────────────────────────────────────
interface Booking {
  id: number;
  reference: string;
  guest_name: string;
  room_id: number;
  check_in: string;
  check_out: string;
  status: string;
  nights: number;
}

interface Room {
  id: number;
  number: string;
  floor: number;
  type_name: string;
  status: string;
  bookings: Booking[];
}

// ─── Booking Detail Modal ─────────────────────────────────────────────────────
function BookingModal({ booking, room, onClose }: { booking: Booking; room: Room; onClose: () => void }) {
  const colors = TYPE_COLORS[room.type_name] ?? TYPE_COLORS['Standard King'];
  return (
    <AnimatePresence>
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          className="absolute inset-0 bg-black/40 backdrop-blur-sm"
          onClick={onClose}
        />
        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 20 }}
          animate={{ opacity: 1, scale: 1,    y: 0  }}
          exit={{    opacity: 0, scale: 0.95, y: 20  }}
          className="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm z-10 overflow-hidden"
        >
          {/* Header */}
          <div className="p-5 text-white" style={{ background: colors.bg }}>
            <div className="flex items-center justify-between mb-3">
              <span className="text-xs font-medium opacity-80">{booking.reference}</span>
              <button onClick={onClose} className="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center hover:bg-white/30 transition-colors">
                <X className="w-4 h-4" />
              </button>
            </div>
            <h3 className="text-xl font-bold">{booking.guest_name}</h3>
            <p className="text-sm opacity-80 mt-1">{room.type_name} · Room {room.number}</p>
          </div>

          {/* Details */}
          <div className="p-5 space-y-3">
            <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
              <Calendar className="w-4 h-4 text-gray-400" />
              <div>
                <p className="text-xs text-gray-400">Check-in</p>
                <p className="text-sm font-semibold">{format(new Date(booking.check_in), 'MMM dd, yyyy')}</p>
              </div>
            </div>
            <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
              <Calendar className="w-4 h-4 text-gray-400" />
              <div>
                <p className="text-xs text-gray-400">Check-out</p>
                <p className="text-sm font-semibold">{format(new Date(booking.check_out), 'MMM dd, yyyy')}</p>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                <Hash className="w-4 h-4 text-gray-400" />
                <div>
                  <p className="text-xs text-gray-400">Nights</p>
                  <p className="text-sm font-semibold">{booking.nights}</p>
                </div>
              </div>
              <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                <User className="w-4 h-4 text-gray-400" />
                <div>
                  <p className="text-xs text-gray-400">Status</p>
                  <p className="text-sm font-semibold capitalize" style={{ color: STATUS_COLORS[booking.status] }}>
                    {booking.status.replace('_', ' ')}
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Actions */}
          <div className="px-5 pb-5 flex gap-2">
            <button className="flex-1 h-9 rounded-xl text-sm font-medium text-white transition-colors" style={{ background: colors.bg }}>
              Edit Booking
            </button>
            <button className="flex-1 h-9 rounded-xl text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
              Check In
            </button>
          </div>
        </motion.div>
      </div>
    </AnimatePresence>
  );
}

// ─── Main Tape Chart ──────────────────────────────────────────────────────────
export default function TapeChartPage() {
  const [startDate, setStartDate] = useState(() => startOfDay(new Date('2026-05-13')));
  const [selectedBooking, setSelectedBooking] = useState<{ booking: Booking; room: Room } | null>(null);
  const scrollRef = useRef<HTMLDivElement>(null);

  const dates = Array.from({ length: DAYS }, (_, i) => addDays(startDate, i));

  const prevMonth = () => setStartDate((d) => addDays(d, -7));
  const nextMonth = () => setStartDate((d) => addDays(d,  7));

  const getBookingStyle = (booking: Booking, room: Room) => {
    const checkIn  = startOfDay(new Date(booking.check_in));
    const checkOut = startOfDay(new Date(booking.check_out));
    const colors   = TYPE_COLORS[room.type_name] ?? TYPE_COLORS['Standard King'];

    const startOffset = differenceInDays(checkIn, startDate);
    const duration    = differenceInDays(checkOut, checkIn);

    if (startOffset + duration < 0 || startOffset >= DAYS) return null;

    const clampedStart    = Math.max(0, startOffset);
    const clampedDuration = Math.min(duration + startOffset, DAYS) - clampedStart;
    if (clampedDuration <= 0) return null;

    return {
      left:   clampedStart * DAY_WIDTH + 2,
      width:  clampedDuration * DAY_WIDTH - 4,
      colors,
    };
  };

  // Group rooms by floor
  const floors = Array.from(new Set(mockTapeChartRooms.map((r) => r.floor))).sort();

  return (
    <div className="p-4 h-full flex flex-col gap-4">
      {/* Header controls */}
      <div className="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h2 className="text-xl font-bold text-white drop-shadow">Tape Chart</h2>
          <p className="text-sm text-white/70">
            {format(startDate, 'MMM dd')} — {format(addDays(startDate, DAYS - 1), 'MMM dd, yyyy')}
          </p>
        </div>

        <div className="flex items-center gap-2">
          {/* Navigation */}
          <button onClick={prevMonth} className="w-8 h-8 rounded-lg bg-white/20 backdrop-blur text-white hover:bg-white/30 flex items-center justify-center transition-colors">
            <ChevronLeft className="w-4 h-4" />
          </button>
          <button
            onClick={() => setStartDate(startOfDay(new Date()))}
            className="px-3 h-8 rounded-lg bg-white text-blue-600 text-xs font-semibold hover:bg-blue-50 transition-colors"
          >
            Today
          </button>
          <button onClick={nextMonth} className="w-8 h-8 rounded-lg bg-white/20 backdrop-blur text-white hover:bg-white/30 flex items-center justify-center transition-colors">
            <ChevronRight className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Legend */}
      <div className="flex items-center gap-3 flex-wrap">
        {Object.entries(TYPE_COLORS).map(([type, colors]) => (
          <div key={type} className="flex items-center gap-1.5 bg-white/10 backdrop-blur px-3 py-1 rounded-full">
            <div className="w-2.5 h-2.5 rounded-full" style={{ background: colors.bg }} />
            <span className="text-xs text-white font-medium">{type}</span>
          </div>
        ))}
      </div>

      {/* Chart */}
      <div className="flex-1 bg-white/90 backdrop-blur rounded-2xl overflow-hidden shadow-xl flex flex-col">
        {/* Sticky date header */}
        <div className="flex border-b border-gray-200 sticky top-0 bg-white z-20">
          {/* Room column header */}
          <div
            className="shrink-0 flex items-center gap-2 px-3 border-r border-gray-200 bg-gray-50"
            style={{ width: ROOM_COL_WIDTH, height: ROW_HEIGHT }}
          >
            <BedDouble className="w-4 h-4 text-gray-400" />
            <span className="text-xs font-semibold text-gray-500">ROOM</span>
          </div>

          {/* Date columns */}
          <div className="flex overflow-hidden" ref={scrollRef}>
            {dates.map((date, i) => (
              <div
                key={i}
                style={{ width: DAY_WIDTH, minWidth: DAY_WIDTH }}
                className={cn(
                  'flex flex-col items-center justify-center border-r border-gray-100 py-1',
                  isToday(date) ? 'bg-blue-50' : isWeekend(date) ? 'bg-gray-50' : 'bg-white'
                )}
              >
                <span className={cn('text-[10px] font-medium', isToday(date) ? 'text-blue-600' : 'text-gray-400')}>
                  {format(date, 'EEE')}
                </span>
                <span className={cn(
                  'text-xs font-bold w-6 h-6 flex items-center justify-center rounded-full',
                  isToday(date) ? 'bg-blue-500 text-white' : 'text-gray-700'
                )}>
                  {format(date, 'd')}
                </span>
              </div>
            ))}
          </div>
        </div>

        {/* Room rows */}
        <div className="overflow-y-auto flex-1">
          {floors.map((floor) => (
            <div key={floor}>
              {/* Floor divider */}
              <div className="flex items-center bg-gray-50 border-b border-gray-200 px-3 py-1">
                <span className="text-xs font-bold text-gray-400 uppercase tracking-wider">
                  Floor {floor}
                </span>
              </div>

              {mockTapeChartRooms
                .filter((r) => r.floor === floor)
                .map((room) => {
                  const statusBadge = ROOM_STATUS_BADGE[room.status] ?? ROOM_STATUS_BADGE['available'];
                  return (
                    <div
                      key={room.id}
                      className="flex border-b border-gray-100 hover:bg-blue-50/30 transition-colors group"
                      style={{ height: ROW_HEIGHT }}
                    >
                      {/* Room info */}
                      <div
                        className="shrink-0 flex items-center gap-2 px-3 border-r border-gray-200"
                        style={{ width: ROOM_COL_WIDTH }}
                      >
                        <div>
                          <p className="text-sm font-bold text-gray-800">#{room.number}</p>
                          <div className="flex items-center gap-1 mt-0.5">
                            <div className="w-1.5 h-1.5 rounded-full" style={{ background: statusBadge.bg }} />
                            <p className="text-[10px] text-gray-400">{room.type_name}</p>
                          </div>
                        </div>
                      </div>

                      {/* Booking blocks */}
                      <div className="flex-1 relative overflow-hidden">
                        {/* Day grid lines */}
                        {dates.map((date, i) => (
                          <div
                            key={i}
                            className={cn(
                              'absolute top-0 bottom-0 border-r border-gray-100',
                              isToday(date) ? 'bg-blue-50/50' : isWeekend(date) ? 'bg-gray-50/50' : ''
                            )}
                            style={{ left: i * DAY_WIDTH, width: DAY_WIDTH }}
                          />
                        ))}

                        {/* Booking blocks */}
                        {room.bookings.map((booking) => {
                          const style = getBookingStyle(booking, room);
                          if (!style) return null;
                          return (
                            <motion.button
                              key={booking.id}
                              whileHover={{ scale: 1.02, zIndex: 10 }}
                              onClick={() => setSelectedBooking({ booking, room })}
                              className="absolute top-2 bottom-2 rounded-lg text-left overflow-hidden shadow-sm cursor-pointer"
                              style={{
                                left:       style.left,
                                width:      style.width,
                                background: style.colors.bg,
                                borderLeft: `3px solid ${style.colors.border}`,
                              }}
                              title={`${booking.guest_name} — ${booking.nights} nights`}
                            >
                              <div className="px-2 py-1 h-full flex flex-col justify-center">
                                <p className="text-xs font-semibold text-white truncate leading-tight">
                                  {booking.guest_name.split(' ')[0]}
                                </p>
                                {style.width > 60 && (
                                  <p className="text-[10px] text-white/70 truncate">
                                    {booking.nights}n · {booking.status.replace('_', ' ')}
                                  </p>
                                )}
                              </div>
                            </motion.button>
                          );
                        })}
                      </div>
                    </div>
                  );
                })}
            </div>
          ))}
        </div>
      </div>

      {/* Booking detail modal */}
      {selectedBooking && (
        <BookingModal
          booking={selectedBooking.booking}
          room={selectedBooking.room}
          onClose={() => setSelectedBooking(null)}
        />
      )}
    </div>
  );
}