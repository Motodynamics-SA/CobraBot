import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(props: ImgHTMLAttributes<HTMLImageElement>) {
	return (
		<img
			{...props}
			src="/images/logo.png"
			alt="App Logo"
			className={`w-auto ${props.className || ''}`}
		/>
	);
}
