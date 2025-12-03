import type { FC } from 'react';
import { useLaunchParams } from '@telegram-apps/sdk-react';
import { Page } from '@/components/Page.tsx';
import './PaxyoWebView.css';

export const PaxyoWebViewPage: FC = () => {
  const launchParams = useLaunchParams();
  const initData = launchParams?.initDataRaw as string | undefined;

  // Construct the URL with the Telegram data
  // We point to telegram_auth.php which will handle the login and then redirect to smm.php
  const webViewUrl = `https://paxyo.com/telegram_auth.php?tg_data=${encodeURIComponent(initData || '')}`;

  return (
    <Page back={false}>
      <div className="webview-container">
        <iframe
          src={webViewUrl}
          className="webview-iframe"
          title="Paxyo Services"
          sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-top-navigation"
          loading="lazy"
        />
      </div>
    </Page>
  );
};
