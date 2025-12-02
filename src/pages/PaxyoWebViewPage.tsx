import type { FC } from 'react';
import { Page } from '@/components/Page.tsx';
import './PaxyoWebView.css';

export const PaxyoWebViewPage: FC = () => {
  return (
    <Page back={false}>
      <div className="webview-container">
        <iframe
          src="https://paxyo.com/smm.php"
          className="webview-iframe"
          title="Paxyo Services"
          sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-top-navigation"
          loading="lazy"
        />
      </div>
    </Page>
  );
};
