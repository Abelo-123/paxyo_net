import type { FC } from 'react';
import { useLaunchParams } from '@telegram-apps/sdk-react';
import { Page } from '@/components/Page.tsx';
import './PaxyoWebView.css';

export const PaxyoWebViewPage: FC = () => {
  const launchParams = useLaunchParams();

  // MOCK DATA FOR DEBUGGING
  // This simulates what Telegram sends. 
  // User: ID=123456789, Name=Abel, Username=abel
  const mockInitData = "query_id=AAG...&user=%7B%22id%22%3A123456789%2C%22first_name%22%3A%22Abel%22%2C%22last_name%22%3A%22%22%2C%22username%22%3A%22abel%22%2C%22language_code%22%3A%22en%22%7D&auth_date=1700000000&hash=mock_hash";

  const initData = (launchParams?.initDataRaw as string | undefined) || mockInitData;

  // Construct the URL with the Telegram data
  // We point to telegram_auth.php which will handle the login and then redirect to smm.php
  const webViewUrl = `https://paxyo.com/telegram_auth.php?tg_data=${encodeURIComponent(initData)}`;

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
