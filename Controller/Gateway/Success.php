<?php
declare(strict_types=1);

namespace Tiptop\PaymentGateway\Controller\Gateway;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface as TransactionBuilder;
use Tiptop\PaymentGateway\Gateway\Config\Config;

class Success extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var TransactionBuilder
     */
    protected $transactionBuilder;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceService $invoiceService
     * @param Transaction $transaction
     * @param CheckoutSession $checkoutSession
     * @param TransactionBuilder $transactionBuilder
     * @param LoggerInterface $logger
     * @param InvoiceSender $invoiceSender
     * @param Config $config
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        Transaction $transaction,
        CheckoutSession $checkoutSession,
        TransactionBuilder $transactionBuilder,
        LoggerInterface $logger,
        InvoiceSender $invoiceSender,
        Config $config
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transactionBuilder = $transactionBuilder;
        $this->transaction = $transaction;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->invoiceSender = $invoiceSender;
        $this->config = $config;
    }

    /**
     * Execute the action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = ['success' => false];
        try {
            $response = $this->getRequest()->getContent();
            // Log the response
            if ($this->config->getValue('debug')) {
                $this->logger->info(__('Response: ') . $response);
            }

            $data = json_decode($response, true);

            $merchantOrderID = $data['merchantOrderID'];
            $checkoutToken = $data['checkoutToken'];
            $publicAppKey = $data['publicAppKey'];

            // Get the current order from the session
            $order = $this->checkoutSession->getLastRealOrder();

            if ($order->getId()) {
                // Generate invoice
                if ($order->canInvoice()) {
                    $invoice = $this->invoiceService->prepareInvoice($order);
                    // Capture the payment online
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                    $invoice->setTransactionId($merchantOrderID);
                    $invoice->register();
                    $invoice->pay();
                    $invoice->save();

                    // Send invoice email
                    $this->invoiceSender->send($invoice);

                    $transactionSave = $this->transaction
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());

                    $transactionSave->save();

                    $order->addCommentToStatusHistory(__('Invoice #%1 created.', $invoice->getId()));
                    $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                }

                $payment = $order->getPayment();
                if (isset($payment)) {
                    // Create the transaction
                    $transaction = $this->transactionBuilder->setPayment($payment)
                        ->setOrder($order)
                        ->setTransactionId($merchantOrderID) // You might want to set this to a unique transaction ID
                        ->setAdditionalInformation([
                            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => [
                                'merchantOrderID' => $merchantOrderID,
                                'checkoutToken' => $checkoutToken,
                                'publicAppKey' => $publicAppKey,
                            ]
                        ])
                        ->setFailSafe(true)
                        ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_ORDER);

                    // Link transaction to the order and save everything
                    $payment->addTransactionCommentsToOrder($transaction, __('Transaction created successfully.'));
                    $transaction->save();

                    // Set last transition id as the checkout token
                    $payment->setLastTransId($checkoutToken);
                    $payment->save();
                } else {
                    $this->logger->critical("Tiptop - Couldn't save transaction id for order: " . $order->getId());
                }

                $this->orderRepository->save($order);

                $result['success'] = true;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
