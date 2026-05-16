import { motion } from 'framer-motion';
import {
  AreaChart, Area, XAxis, YAxis, Tooltip, ResponsiveContainer,
  PieChart, Pie, Cell,
} from 'recharts';
import {
  BedDouble, TrendingUp, LogIn, LogOut,
  Sparkles, DollarSign, ArrowUpRight,
} from 'lucide-react';
import {
  mockStats, mockOccupancyWeek, mockRoomStatus,
  mockRecentBookings, mockArrivals,
} from '@/lib/mockData';

import { cn } from '@/lib/utils';

const fadeUp = {
  hidden: { opacity: 0, y: 24 },
  show:   { opacity: 1, y: 0  },
};
const stagger = {
  show: { transition: { staggerChildren: 0.08 } },
};

const STATUS_STYLES: Record<string, string> = {
  confirmed:   'bg-blue-100 text-blue-700',
  checked_in:  'bg-green-100 text-green-700',
  checked_out: 'bg-gray-100 text-gray-600',
  cancelled:   'bg-red-100 text-red-600',
  pending:     'bg-yellow-100 text-yellow-700',
};

function StatusBadge({ status }: { status: string }) {
  return (
    <span className={cn(
      'px-2 py-0.5 rounded-full text-xs font-medium capitalize',
      STATUS_STYLES[status] ?? 'bg-gray-100 text-gray-600'
    )}>
      {status.replace('_', ' ')}
    </span>
  );
}

interface StatCardProps {
  label: string;
  value: string | number;
  icon: React.ReactNode;
  gradient: string;
  sub?: string;
}

function StatCard({ label, value, icon, gradient, sub }: StatCardProps) {
  return (
    <motion.div
      variants={fadeUp}
      className={cn('rounded-2xl p-5 text-white shadow-lg relative overflow-hidden', gradient)}
    >
      <div className="absolute -right-4 -top-4 w-24 h-24 rounded-full bg-white/10" />
      <div className="absolute -right-2 -bottom-6 w-32 h-32 rounded-full bg-white/5" />
      <div className="relative z-10">
        <div className="flex items-center justify-between mb-4">
          <div className="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
            {icon}
          </div>
          <ArrowUpRight className="w-4 h-4 text-white/60" />
        </div>
        <p className="text-3xl font-bold mb-1">{value}</p>
        <p className="text-white/80 text-sm">{label}</p>
        {sub && <p className="text-white/60 text-xs mt-1">{sub}</p>}
      </div>
    </motion.div>
  );
}

export default function DashboardPage() {
  return (
  <div className="p-6 space-y-5 min-h-screen">

      

      {/* Scrollable content */}
      <motion.div
        variants={stagger}
        initial="hidden"
        animate="show"
        className="relative z-10 p-6 space-y-5 min-h-screen"
      >
        {/* Welcome banner — white card */}
        <motion.div
          variants={fadeUp}
          className="rounded-2xl bg-white p-5 shadow-xl"
        >
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-xl font-bold text-gray-800 mb-1">Good morning! 👋</h2>
              <p className="text-gray-500 text-sm">Here's what's happening at your hotel today.</p>
            </div>
            <div className="text-right bg-blue-50 rounded-xl px-4 py-2">
              <p className="text-3xl font-bold text-blue-600">{mockStats.occupancyRate}%</p>
              <p className="text-gray-400 text-xs">Occupancy Rate</p>
            </div>
          </div>
          <div className="mt-3">
            <div className="h-2 bg-blue-100 rounded-full overflow-hidden">
              <motion.div
                className="h-full bg-blue-500 rounded-full"
                initial={{ width: 0 }}
                animate={{ width: `${mockStats.occupancyRate}%` }}
                transition={{ duration: 2.2, ease: 'easeOut', delay: 0.3 }}
              />
            </div>
            <div className="flex justify-between mt-1 text-xs text-gray-400">
              <span>{mockStats.occupiedRooms} occupied</span>
              <span>{mockStats.availableRooms} available</span>
            </div>
          </div>
        </motion.div>

        {/* Stat cards */}
        <motion.div variants={stagger} className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard
            label="Total Rooms"
            value={mockStats.totalRooms}
            icon={<BedDouble className="w-5 h-5 text-white" />}
            gradient="bg-gradient-to-br from-violet-500 to-purple-700"
            sub="Across all floors"
          />
          <StatCard
            label="Check-ins Today"
            value={mockStats.checkInsToday}
            icon={<LogIn className="w-5 h-5 text-white" />}
            gradient="bg-gradient-to-br from-emerald-400 to-teal-600"
            sub="3 remaining"
          />
          <StatCard
            label="Check-outs Today"
            value={mockStats.checkOutsToday}
            icon={<LogOut className="w-5 h-5 text-white" />}
            gradient="bg-gradient-to-br from-orange-400 to-pink-600"
            sub="All processed"
          />
          <StatCard
            label="Revenue Today"
            value={`$${mockStats.revenueToday.toLocaleString()}`}
            icon={<DollarSign className="w-5 h-5 text-white" />}
            gradient="bg-gradient-to-br from-blue-500 to-indigo-700"
            sub="+12% vs yesterday"
          />
        </motion.div>

        {/* Charts */}
        <motion.div variants={stagger} className="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <motion.div variants={fadeUp} className="lg:col-span-2 bg-white/90 backdrop-blur rounded-2xl p-5">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h3 className="font-semibold text-gray-800">Weekly Occupancy</h3>
                <p className="text-xs text-gray-400">Last 7 days</p>
              </div>
              <TrendingUp className="w-5 h-5 text-blue-500" />
            </div>
            <ResponsiveContainer width="100%" height={160}>
              <AreaChart data={mockOccupancyWeek}>
                <defs>
                  <linearGradient id="occGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%"  stopColor="#3b82f6" stopOpacity={0.3} />
                    <stop offset="95%" stopColor="#3b82f6" stopOpacity={0}   />
                  </linearGradient>
                </defs>
                <XAxis dataKey="day" tick={{ fontSize: 11 }} axisLine={false} tickLine={false} />
                <YAxis domain={[0,100]} tick={{ fontSize: 11 }} axisLine={false} tickLine={false} tickFormatter={(v) => `${v}%`} />
                <Tooltip formatter={(v) => [`${v}%`, 'Occupancy']} />
                <Area type="monotone" dataKey="rate" stroke="#3b82f6" strokeWidth={2} fill="url(#occGrad)" />
              </AreaChart>
            </ResponsiveContainer>
          </motion.div>

          <motion.div variants={fadeUp} className="bg-white/90 backdrop-blur rounded-2xl p-5">
            <div className="flex items-center justify-between mb-2">
              <div>
                <h3 className="font-semibold text-gray-800">Room Status</h3>
                <p className="text-xs text-gray-400">Right now</p>
              </div>
              <BedDouble className="w-5 h-5 text-blue-500" />
            </div>
            <ResponsiveContainer width="100%" height={130}>
              <PieChart>
                <Pie data={mockRoomStatus} cx="50%" cy="50%" innerRadius={35} outerRadius={55} paddingAngle={3} dataKey="value">
                  {mockRoomStatus.map((entry, i) => (
                    <Cell key={i} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
            <div className="space-y-1 mt-1">
              {mockRoomStatus.map((s) => (
                <div key={s.name} className="flex items-center justify-between text-xs">
                  <div className="flex items-center gap-2">
                    <div className="w-2 h-2 rounded-full" style={{ backgroundColor: s.color }} />
                    <span className="text-gray-500">{s.name}</span>
                  </div>
                  <span className="font-semibold text-gray-700">{s.value}</span>
                </div>
              ))}
            </div>
          </motion.div>
        </motion.div>

        {/* Bottom row */}
        <motion.div variants={stagger} className="grid grid-cols-1 lg:grid-cols-2 gap-4 pb-6">
          <motion.div variants={fadeUp} className="bg-white/90 backdrop-blur rounded-2xl p-5">
            <h3 className="font-semibold text-gray-800 mb-3">Recent Bookings</h3>
            <div className="space-y-2">
              {mockRecentBookings.map((b) => (
                <div key={b.id} className="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                  <div>
                    <p className="text-sm font-medium text-gray-800">{b.guest}</p>
                    <p className="text-xs text-gray-400">Room {b.room} · {b.type}</p>
                  </div>
                  <StatusBadge status={b.status} />
                </div>
              ))}
            </div>
          </motion.div>

          <motion.div variants={fadeUp} className="bg-white/90 backdrop-blur rounded-2xl p-5">
            <div className="flex items-center justify-between mb-3">
              <h3 className="font-semibold text-gray-800">Today's Arrivals</h3>
              <span className="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">
                {mockArrivals.length} guests
              </span>
            </div>
            <div className="space-y-2">
              {mockArrivals.map((a) => (
                <motion.div
                  key={a.id}
                  whileHover={{ scale: 1.01 }}
                  className="flex items-center gap-3 p-3 rounded-xl bg-blue-50 cursor-pointer"
                >
                  <div className="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center text-white font-bold text-sm shrink-0">
                    {a.guest[0]}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-800 truncate">{a.guest}</p>
                    <p className="text-xs text-gray-400">Room {a.room} · {a.nights} night{a.nights > 1 ? 's' : ''}</p>
                  </div>
                  <div className="text-right shrink-0">
                    <p className="text-xs font-medium text-blue-600">{a.time}</p>
                    <p className="text-xs text-gray-400">ETA</p>
                  </div>
                </motion.div>
              ))}
            </div>
            <div className="mt-3 p-3 rounded-xl bg-amber-50 border border-amber-200 flex items-center gap-3">
              <Sparkles className="w-4 h-4 text-amber-500 shrink-0" />
              <p className="text-xs text-amber-700">
                <span className="font-semibold">{mockStats.pendingHousekeeping} rooms</span> pending housekeeping before next check-in.
              </p>
            </div>
          </motion.div>
        </motion.div>
      </motion.div>
    </div>
  );
}