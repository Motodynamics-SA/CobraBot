import React from 'react';
import { render, screen } from '@testing-library/react';
import DashboardSimple from '../dashboard-simple';

// Mock the AppLayout component
jest.mock('@/layouts/app-layout', () => {
	return jest.fn(({ children }) => {
		return <div data-testid="app-layout">{children}</div>;
	});
});

// Mock the Button component
jest.mock('@/components/ui/button', () => ({
	Button: jest.fn(({ children }) => <button data-testid="button">{children}</button>),
}));

// Mock the ArrowRight icon
jest.mock('lucide-react', () => ({
	ArrowRight: jest.fn(() => <div data-testid="arrow-right-icon" />),
}));

describe('DashboardSimple Component', () => {
	it('renders the simple dashboard with title and hero text', () => {
		render(<DashboardSimple />);

		// Check for the title and hero title
		expect(screen.getByText('common.welcome_title')).toBeInTheDocument();
		expect(screen.getByText('common.welcome_subtitle')).toBeInTheDocument();
	});
});
