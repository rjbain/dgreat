<?php

namespace Drupal\myusf_site_chat\Controller;

use Drupal\Core\Controller\ControllerBase;

final class SiteChatController extends ControllerBase {

  public function page(): array {
    $block = \Drupal::service('plugin.manager.block')->createInstance('ai_deepchat_block', [
      'chat_processor_plugin' => 'myusf_site_chat',
      'bot_name' => 'MyUSF guide',
      'first_message' => 'What would you like to know about MyUSF? I answer using published site content and link to my sources.',
      'width' => '100%',
      'height' => '600px',
      'placement' => 'content',
      'style_file' => 'module:ai_chatbot:toolbar.yml',
      'stream' => FALSE,
    ]);
    $chat = $block->build();
    $chat['#theme'] = 'myusf_site_chat_widget';
    return [
      'intro' => ['#markup' => '<p>Ask a question about information on this website. Answers are AI generated; check the linked sources. Your question and relevant site excerpts are sent to OpenAI.</p>'],
      'chat' => $chat,
      '#cache' => ['max-age' => 0],
    ];
  }

}
