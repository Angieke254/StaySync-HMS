import { useEffect, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import {
  Search,
  Bell,
  Calendar,
  Users,
  BedDouble,
  CreditCard,
  CheckCircle2,
  Clock3,
  Plus,
} from "lucide-react";

// ─────────────────────────────────────────────────────────
// Background Images
// ─────────────────────────────────────────────────────────
const backgrounds = [
  "https://images.unsplash.com/photo-1566073771259-6a8506099945",
  "https://images.unsplash.com/photo-1522798514-97ceb8c4f1c8",
  "https://images.unsplash.com/photo-1551882547-ff40c63fe5fa",
  "https://images.unsplash.com/photo-1445019980597-93fa8acb246c",
];

// ─────────────────────────────────────────────────────────
// Mock Booking Data
// ─────────────────────────────────────────────────────────
const bookings = [
  {
    id: 1,
    guest: "Angela Atieno",
    room: "Deluxe King",
    checkIn: "15 May 2026",
    checkOut: "18 May 2026",
    amount: "$420",
    status: "Checked In",
  },
  {
    id: 2,
    guest: "Brian Otieno",
    room: "Suite",
    checkIn: "16 May 2026",
    checkOut: "20 May 2026",
    amount: "$760",
    status: "Pending",
  },
  {
    id: 3,
    guest: "Faith Achieng",
    room: "Penthouse",
    checkIn: "17 May 2026",
    checkOut: "21 May 2026",
    amount: "$1200",
    status: "Confirmed",
  },
];

// ─────────────────────────────────────────────────────────
// Status Badge
// ─────────────────────────────────────────────────────────
function StatusBadge({ status }: { status: string }) {
  const styles: Record<string, string> = {
    Confirmed: "bg-blue-500/20 text-blue-100",
    Pending: "bg-yellow-500/20 text-yellow-100",
    "Checked In": "bg-green-500/20 text-green-100",
  };

  return (
    <span
      className={`px-3 py-1 rounded-full text-xs font-semibold ${styles[status]}`}
    >
      {status}
    </span>
  );
}

// ─────────────────────────────────────────────────────────
// Main Dashboard
// ─────────────────────────────────────────────────────────
export default function BookingDashboard() {
  const [currentBg, setCurrentBg] = useState(0);

  // Background slideshow
  useEffect(() => {
    const interval = setInterval(() => {
      setCurrentBg((prev) => (prev + 1) % backgrounds.length);
    }, 5000);

    return () => clearInterval(interval);
  }, []);

  return (
    <div className="relative min-h-screen overflow-hidden">
      {/* Background Slideshow */}
      <AnimatePresence mode="wait">
        <motion.div
          key={currentBg}
          initial={{ opacity: 0, scale: 1.05 }}
          animate={{ opacity: 1, scale: 1 }}
          exit={{ opacity: 0 }}
          transition={{ duration: 1 }}
          className="absolute inset-0 bg-cover bg-center"
          style={{
            backgroundImage: `url(${backgrounds[currentBg]})`,
          }}
        />
      </AnimatePresence>

      {/* Overlay */}
      <div className="absolute inset-0 bg-black/60 backdrop-blur-sm" />

      {/* Main Content */}
      <div className="relative z-10 p-6 flex flex-col gap-6">
        {/* Header */}
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <h1 className="text-4xl font-bold text-white">
              Booking Dashboard
            </h1>
            <p className="text-white/70 mt-1">
              Manage reservations, guests and hotel operations
            </p>
          </div>

          <div className="flex items-center gap-3">
            {/* Search */}
            <div className="flex items-center gap-2 bg-white/10 backdrop-blur px-4 py-3 rounded-2xl border border-white/10">
              <Search className="w-4 h-4 text-white/70" />
              <input
                placeholder="Search bookings..."
                className="bg-transparent outline-none text-sm text-white placeholder:text-white/50"
              />
            </div>

            {/* Notifications */}
            <button className="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur border border-white/10 flex items-center justify-center text-white hover:bg-white/20 transition">
              <Bell className="w-5 h-5" />
            </button>

            {/* Add Booking */}
            <button className="h-12 px-5 rounded-2xl bg-blue-600 text-white font-semibold flex items-center gap-2 hover:bg-blue-700 transition shadow-lg">
              <Plus className="w-4 h-4" />
              New Booking
            </button>
          </div>
        </div>

        {/* Dashboard Stats */}
        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
          <div className="bg-white/10 backdrop-blur-xl border border-white/10 rounded-3xl p-5">
            <div className="flex items-center justify-between">
              <Calendar className="w-8 h-8 text-blue-300" />
              <span className="text-xs text-white/60">Today</span>
            </div>

            <h2 className="text-3xl font-bold text-white mt-5">128</h2>
            <p className="text-white/70 text-sm mt-1">Total Reservations</p>
          </div>

          <div className="bg-white/10 backdrop-blur-xl border border-white/10 rounded-3xl p-5">
            <div className="flex items-center justify-between">
              <BedDouble className="w-8 h-8 text-green-300" />
              <span className="text-xs text-white/60">Rooms</span>
            </div>

            <h2 className="text-3xl font-bold text-white mt-5">84%</h2>
            <p className="text-white/70 text-sm mt-1">Occupancy Rate</p>
          </div>

          <div className="bg-white/10 backdrop-blur-xl border border-white/10 rounded-3xl p-5">
            <div className="flex items-center justify-between">
              <Users className="w-8 h-8 text-purple-300" />
              <span className="text-xs text-white/60">Guests</span>
            </div>

            <h2 className="text-3xl font-bold text-white mt-5">64</h2>
            <p className="text-white/70 text-sm mt-1">Checked In</p>
          </div>

          <div className="bg-white/10 backdrop-blur-xl border border-white/10 rounded-3xl p-5">
            <div className="flex items-center justify-between">
              <CreditCard className="w-8 h-8 text-yellow-300" />
              <span className="text-xs text-white/60">Revenue</span>
            </div>

            <h2 className="text-3xl font-bold text-white mt-5">$12.4K</h2>
            <p className="text-white/70 text-sm mt-1">Today's Revenue</p>
          </div>
        </div>

        {/* Booking Table */}
        <div className="bg-white/10 backdrop-blur-2xl border border-white/10 rounded-3xl overflow-hidden">
          {/* Table Header */}
          <div className="flex items-center justify-between p-5 border-b border-white/10">
            <div>
              <h3 className="text-xl font-bold text-white">
                Recent Bookings
              </h3>
              <p className="text-sm text-white/60">
                Latest reservations and room activities
              </p>
            </div>

            <div className="flex gap-2">
              <button className="px-4 py-2 rounded-xl bg-white/10 text-white text-sm hover:bg-white/20 transition">
                Filter
              </button>

              <button className="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm hover:bg-blue-700 transition">
                Export
              </button>
            </div>
          </div>

          {/* Table */}
          <div className="overflow-x-auto">
            <table className="w-full min-w-[900px]">
              <thead>
                <tr className="text-left border-b border-white/10">
                  <th className="px-6 py-4 text-xs font-semibold text-white/60">
                    Guest
                  </th>

                  <th className="px-6 py-4 text-xs font-semibold text-white/60">
                    Room
                  </th>

                  <th className="px-6 py-4 text-xs font-semibold text-white/60">
                    Check In
                  </th>

                  <th className="px-6 py-4 text-xs font-semibold text-white/60">
                    Check Out
                  </th>

                  <th className="px-6 py-4 text-xs font-semibold text-white/60">
                    Amount
                  </th>

                  <th className="px-6 py-4 text-xs font-semibold text-white/60">
                    Status
                  </th>

                  <th className="px-6 py-4 text-xs font-semibold text-white/60">
                    Actions
                  </th>
                </tr>
              </thead>

              <tbody>
                {bookings.map((booking) => (
                  <motion.tr
                    key={booking.id}
                    whileHover={{ backgroundColor: "rgba(255,255,255,0.04)" }}
                    className="border-b border-white/5"
                  >
                    <td className="px-6 py-5">
                      <div className="flex items-center gap-3">
                        <div className="w-11 h-11 rounded-2xl bg-blue-500 flex items-center justify-center text-white font-bold">
                          {booking.guest.charAt(0)}
                        </div>

                        <div>
                          <p className="text-white font-semibold">
                            {booking.guest}
                          </p>

                          <p className="text-xs text-white/50">
                            Premium Guest
                          </p>
                        </div>
                      </div>
                    </td>

                    <td className="px-6 py-5 text-white">
                      {booking.room}
                    </td>

                    <td className="px-6 py-5 text-white/80">
                      {booking.checkIn}
                    </td>

                    <td className="px-6 py-5 text-white/80">
                      {booking.checkOut}
                    </td>

                    <td className="px-6 py-5 text-green-300 font-semibold">
                      {booking.amount}
                    </td>

                    <td className="px-6 py-5">
                      <StatusBadge status={booking.status} />
                    </td>

                    <td className="px-6 py-5">
                      <div className="flex gap-2">
                        <button className="px-4 py-2 rounded-xl bg-white/10 text-white text-sm hover:bg-white/20 transition">
                          View
                        </button>

                        <button className="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm hover:bg-blue-700 transition">
                          Edit
                        </button>
                      </div>
                    </td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Bottom Analytics */}
        <div className="grid grid-cols-1 xl:grid-cols-3 gap-4">
          <div className="bg-white/10 backdrop-blur-xl border border-white/10 rounded-3xl p-5">
            <div className="flex items-center gap-2">
              <Clock3 className="w-5 h-5 text-yellow-300" />
              <h3 className="text-lg font-bold text-white">
                Pending Check-ins
              </h3>
            </div>

            <div className="mt-5 space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-white/70">Suite 204</span>
                <span className="text-yellow-300 text-sm">
                  Arrives 2PM
                </span>
              </div>

              <div className="flex items-center justify-between">
                <span className="text-white/70">Deluxe 108</span>
                <span className="text-yellow-300 text-sm">
                  Arrives 4PM
                </span>
              </div>
            </div>
          </div>

          <div className="bg-white/10 backdrop-blur-xl border border-white/10 rounded-3xl p-5">
            <div className="flex items-center gap-2">
              <CheckCircle2 className="w-5 h-5 text-green-300" />
              <h3 className="text-lg font-bold text-white">
                Room Status
              </h3>
            </div>

            <div className="mt-5 grid grid-cols-2 gap-3">
              <div className="bg-green-500/20 rounded-2xl p-4">
                <h4 className="text-2xl font-bold text-white">42</h4>
                <p className="text-sm text-white/70">Available</p>
              </div>

              <div className="bg-red-500/20 rounded-2xl p-4">
                <h4 className="text-2xl font-bold text-white">6</h4>
                <p className="text-sm text-white/70">Maintenance</p>
              </div>
            </div>
          </div>

          <div className="bg-white/10 backdrop-blur-xl border border-white/10 rounded-3xl p-5">
            <h3 className="text-lg font-bold text-white">
              Revenue Summary
            </h3>

            <div className="mt-5 flex items-end gap-2 h-40">
              {[40, 80, 60, 100, 120, 90, 140].map((h, i) => (
                <motion.div
                  key={i}
                  initial={{ height: 0 }}
                  animate={{ height: h }}
                  transition={{ delay: i * 0.1 }}
                  className="flex-1 bg-blue-500/70 rounded-t-2xl"
                />
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}