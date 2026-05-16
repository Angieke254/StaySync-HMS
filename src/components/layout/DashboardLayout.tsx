import { Outlet } from 'react-router-dom';
import { Sidebar } from './Sidebar';
import { Header } from './Header';
import { useUIStore } from '@/app/store/uiStore';
import { HotelSlideshow } from '@/components/common/HotelSlideshow';

export function DashboardLayout() {
  const sidebarOpen = useUIStore((s) => s.sidebarOpen);

  return (
    <div style={{ display: 'flex', height: '100vh', overflow: 'hidden' }}>
      {/* Sidebar */}
      <Sidebar />

      {/* Main content area */}
      <div style={{
        display: 'flex',
        flexDirection: 'column',
        flex: 1,
        marginLeft: sidebarOpen ? '240px' : '64px',
        transition: 'margin-left 0.3s',
        overflow: 'hidden',
        position: 'relative',
      }}>

        {/* Background slideshow behind everything */}
        <div style={{
          position: 'absolute',
          inset: 0,
          zIndex: 0,
        }}>
          <HotelSlideshow interval={6000} showLabel={false} overlay={false} />
          <div style={{
            position: 'absolute',
            inset: 0,
            background: 'rgba(0,0,0,0.25)',
          }} />
        </div>

        {/* Header */}
        <div style={{ position: 'relative', zIndex: 10 }}>
          <Header />
        </div>

        {/* Page content */}
        <main style={{
          flex: 1,
          overflowY: 'auto',
          position: 'relative',
          zIndex: 10,
        }}>
          <Outlet />
        </main>
      </div>
    </div>
  );
}