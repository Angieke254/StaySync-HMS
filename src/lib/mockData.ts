export const mockStats = {
  occupancyRate: 78,
  totalRooms: 50,
  occupiedRooms: 39,
  availableRooms: 11,
  checkInsToday: 8,
  checkOutsToday: 5,
  revenueToday: 4200,
  pendingHousekeeping: 7,
};

export const mockOccupancyWeek = [
  { day: 'Mon', rate: 65 },
  { day: 'Tue', rate: 72 },
  { day: 'Wed', rate: 80 },
  { day: 'Thu', rate: 78 },
  { day: 'Fri', rate: 91 },
  { day: 'Sat', rate: 95 },
  { day: 'Sun', rate: 78 },
];

export const mockRoomStatus = [
  { name: 'Occupied',    value: 39, color: '#6366f1' },
  { name: 'Available',   value: 11, color: '#22c55e' },
  { name: 'Dirty',       value: 7,  color: '#f59e0b' },
  { name: 'Maintenance', value: 3,  color: '#ef4444' },
];

export const mockRecentBookings = [
  { id: 1, guest: 'James Odhiambo', room: '101', type: 'Deluxe King',   checkIn: '2026-05-14', checkOut: '2026-05-17', status: 'checked_in' },
  { id: 2, guest: 'Amina Hassan',   room: '205', type: 'Twin Standard', checkIn: '2026-05-14', checkOut: '2026-05-15', status: 'confirmed'  },
  { id: 3, guest: 'Brian Mutua',    room: '312', type: 'Suite',         checkIn: '2026-05-13', checkOut: '2026-05-16', status: 'checked_in' },
  { id: 4, guest: 'Grace Wanjiru',  room: '408', type: 'Deluxe Twin',   checkIn: '2026-05-14', checkOut: '2026-05-18', status: 'confirmed'  },
  { id: 5, guest: 'Peter Otieno',   room: '110', type: 'Standard King', checkIn: '2026-05-12', checkOut: '2026-05-14', status: 'checked_out'},
];

export const mockArrivals = [
  { id: 1, guest: 'Fatuma Ali',     room: '203', time: '14:00', nights: 3 },
  { id: 2, guest: 'David Kamau',    room: '307', time: '15:30', nights: 1 },
  { id: 3, guest: 'Sandra Achieng', room: '102', time: '16:00', nights: 5 },
];
export const mockTapeChartRooms = [
  {
    id: 1, number: '101', floor: 1, type_name: 'Standard King', status: 'occupied',
    bookings: [
      { id: 101, reference: 'SS-001', guest_name: 'James Odhiambo', room_id: 1, check_in: '2026-05-13', check_out: '2026-05-17', status: 'checked_in', nights: 4 },
      { id: 102, reference: 'SS-002', guest_name: 'Mary Wanjiku',   room_id: 1, check_in: '2026-05-20', check_out: '2026-05-23', status: 'confirmed',  nights: 3 },
    ]
  },
  {
    id: 2, number: '102', floor: 1, type_name: 'Standard King', status: 'available',
    bookings: [
      { id: 103, reference: 'SS-003', guest_name: 'Sandra Achieng', room_id: 2, check_in: '2026-05-15', check_out: '2026-05-20', status: 'confirmed', nights: 5 },
    ]
  },
  {
    id: 3, number: '201', floor: 2, type_name: 'Deluxe King', status: 'available',
    bookings: [
      { id: 104, reference: 'SS-004', guest_name: 'Brian Mutua',   room_id: 3, check_in: '2026-05-14', check_out: '2026-05-16', status: 'checked_in', nights: 2 },
      { id: 105, reference: 'SS-005', guest_name: 'Aisha Mohamed', room_id: 3, check_in: '2026-05-22', check_out: '2026-05-27', status: 'confirmed',  nights: 5 },
    ]
  },
  {
    id: 4, number: '202', floor: 2, type_name: 'Deluxe King', status: 'dirty',
    bookings: [
      { id: 106, reference: 'SS-006', guest_name: 'Peter Otieno',  room_id: 4, check_in: '2026-05-15', check_out: '2026-05-19', status: 'confirmed', nights: 4 },
    ]
  },
  {
    id: 5, number: '203', floor: 2, type_name: 'Deluxe Twin', status: 'available',
    bookings: [
      { id: 107, reference: 'SS-007', guest_name: 'Fatuma Ali',    room_id: 5, check_in: '2026-05-15', check_out: '2026-05-18', status: 'checked_in', nights: 3 },
      { id: 108, reference: 'SS-008', guest_name: 'John Kamau',    room_id: 5, check_in: '2026-05-25', check_out: '2026-05-30', status: 'confirmed',  nights: 5 },
    ]
  },
  {
    id: 6, number: '301', floor: 3, type_name: 'Suite', status: 'occupied',
    bookings: [
      { id: 109, reference: 'SS-009', guest_name: 'Grace Wanjiru', room_id: 6, check_in: '2026-05-13', check_out: '2026-05-18', status: 'checked_in', nights: 5 },
      { id: 110, reference: 'SS-010', guest_name: 'David Kimani',  room_id: 6, check_in: '2026-05-21', check_out: '2026-05-25', status: 'confirmed',  nights: 4 },
    ]
  },
  {
    id: 7, number: '302', floor: 3, type_name: 'Suite', status: 'available',
    bookings: [
      { id: 111, reference: 'SS-011', guest_name: 'Amina Hassan',  room_id: 7, check_in: '2026-05-17', check_out: '2026-05-22', status: 'confirmed', nights: 5 },
    ]
  },
  {
    id: 8, number: '401', floor: 4, type_name: 'Penthouse', status: 'available',
    bookings: [
      { id: 112, reference: 'SS-012', guest_name: 'Robert Mwangi', room_id: 8, check_in: '2026-05-20', check_out: '2026-05-25', status: 'confirmed', nights: 5 },
    ]
  },
];