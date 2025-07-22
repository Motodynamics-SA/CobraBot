import React from 'react';
import { render, screen } from '@testing-library/react';
import Dashboard from '../dashboard';

// Mock the AppLayout component
jest.mock('@/layouts/app-layout', () => {
	return jest.fn(({ children }) => {
		return <div data-testid="app-layout">{children}</div>;
	});
});

// Mock any other components used in Dashboard that aren't relevant to the test
jest.mock('@/components/ui/card', () => ({
	Card: jest.fn(({ children }) => <div data-testid="card">{children}</div>),
	CardHeader: jest.fn(({ children }) => <div data-testid="card-header">{children}</div>),
	CardTitle: jest.fn(({ children }) => <div data-testid="card-title">{children}</div>),
	CardContent: jest.fn(({ children }) => <div data-testid="card-content">{children}</div>),
}));

jest.mock('@/components/ui/button', () => ({
	Button: jest.fn(({ children }) => <button data-testid="button">{children}</button>),
}));

jest.mock('lucide-react', () => ({
	Users: jest.fn(() => <div data-testid="users-icon" />),
	ShieldCheck: jest.fn(() => <div data-testid="shield-check-icon" />),
	Clock: jest.fn(() => <div data-testid="clock-icon" />),
	ThumbsUp: jest.fn(() => <div data-testid="thumbs-up-icon" />),
	ThumbsDown: jest.fn(() => <div data-testid="thumbs-down-icon" />),
	ArrowRight: jest.fn(() => <div data-testid="arrow-right-icon" />),
}));

jest.mock('@/utils/format', () => ({
	formatDate: jest.fn(() => 'formatted-date'),
	formatRelativeTime: jest.fn(() => 'time-ago'),
}));

const mockStats = {
	users: {
		total: 100,
		active: 90,
		deleted: 10,
		by_role: {
			admin: 5,
			'user-manager': 15,
			'registered-user': 80,
		},
		recent: {
			week: 5,
			month: 20,
		},
	},
	recent_users: [
		{
			id: 1,
			name: 'Test User',
			email: 'test@example.com',
			created_at: '2025-04-01T00:00:00.000Z',
			role: 'admin',
		},
	],
};

describe('Dashboard Component', () => {
	it('renders the dashboard with correct stats', () => {
		render(<Dashboard stats={mockStats} token="test-token" />);

		// Check for total users
		expect(screen.getByText('dashboard.total_users')).toBeInTheDocument();
		expect(screen.getByText('100')).toBeInTheDocument();
		expect(screen.getByText('5 dashboard.new_users_this_week')).toBeInTheDocument();

		// Check for active users
		expect(screen.getByText('dashboard.active_users')).toBeInTheDocument();
		expect(screen.getByText('90')).toBeInTheDocument();
		expect(screen.getByText('10 dashboard.deleted_users')).toBeInTheDocument();

		// Check for recent activity
		expect(screen.getByText('dashboard.recent_activity')).toBeInTheDocument();
		expect(screen.getByText('20')).toBeInTheDocument();
		expect(screen.getByText('dashboard.new_users_this_month')).toBeInTheDocument();
	});

	it('renders user roles correctly', () => {
		render(<Dashboard stats={mockStats} token="test-token" />);

		// Check for roles section
		expect(screen.getByText('dashboard.users_by_role')).toBeInTheDocument();

		// "roles.admin" because we mocked the translation function to return the key
		expect(screen.getByText('roles.admin')).toBeInTheDocument();
		expect(screen.getByText('5 dashboard.users')).toBeInTheDocument();

		expect(screen.getByText('roles.user-manager')).toBeInTheDocument();
		expect(screen.getByText('15 dashboard.users')).toBeInTheDocument();

		expect(screen.getByText('roles.registered-user')).toBeInTheDocument();
		expect(screen.getByText('80 dashboard.users')).toBeInTheDocument();
	});

	it('renders recent users', () => {
		render(<Dashboard stats={mockStats} token="test-token" />);

		expect(screen.getByText('dashboard.recent_users')).toBeInTheDocument();
		expect(screen.getByText('Test User')).toBeInTheDocument();
	});
});
